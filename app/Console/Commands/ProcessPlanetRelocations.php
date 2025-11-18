<?php

namespace OGame\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use OGame\GameObjects\Models\Coordinate;
use OGame\GameObjects\Models\UnitCollection;
use OGame\Models\BuildingQueue;
use OGame\Models\Enums\PlanetType;
use OGame\Models\FleetMission;
use OGame\Models\Planet;
use OGame\Models\PlanetRelocation;
use OGame\Models\RepairQueue;
use OGame\Models\Resources;
use OGame\Models\ResearchQueue;
use OGame\Models\User;
use OGame\Services\FleetMissionService;
use OGame\Services\PlanetServiceFactory;
use OGame\Services\PlayerService;

class ProcessPlanetRelocations extends Command
{
    protected $signature = 'ogame:process-relocations';
    protected $description = 'Process pending planet relocations after 24-hour countdown';

    public function handle(PlanetServiceFactory $planetServiceFactory, FleetMissionService $fleetMissionService): int
    {
        $currentTime = Carbon::now()->timestamp;

        // Find all relocations that are due to execute
        $relocations = PlanetRelocation::where('time_end', '<=', $currentTime)
            ->where('processed', false)
            ->where('cancelled', false)
            ->get();

        if ($relocations->isEmpty()) {
            $this->info('No relocations to process.');
            return 0;
        }

        $this->info('Processing ' . $relocations->count() . ' relocation(s)...');

        foreach ($relocations as $relocation) {
            try {
                $this->processRelocation($relocation, $planetServiceFactory, $fleetMissionService);
            } catch (\Exception $e) {
                $this->error('Error processing relocation ID ' . $relocation->id . ': ' . $e->getMessage());
            }
        }

        $this->info('Relocation processing complete.');
        return 0;
    }

    private function processRelocation(PlanetRelocation $relocation, PlanetServiceFactory $planetServiceFactory, FleetMissionService $fleetMissionService): void
    {
        $planet = Planet::find($relocation->planet_id);
        if (!$planet) {
            $relocation->cancelled = true;
            $relocation->cancel_reason = 'Planet not found';
            $relocation->save();
            $this->warn('Planet ' . $relocation->planet_id . ' not found. Cancelling relocation.');
            return;
        }

        // Check 1: No active building construction
        $activeBuilding = BuildingQueue::where('planet_id', $planet->id)
            ->where('building', 1)
            ->where('processed', 0)
            ->where('canceled', 0)
            ->first();

        if ($activeBuilding) {
            $relocation->cancelled = true;
            $relocation->cancel_reason = 'Building construction in progress';
            $relocation->save();
            $this->warn('Relocation cancelled for planet ' . $planet->id . ': building construction in progress');
            return;
        }

        // Check 2: No active research (on this specific planet)
        $activeResearch = ResearchQueue::where('planet_id', $planet->id)
            ->where('building', 1)
            ->where('processed', 0)
            ->where('canceled', 0)
            ->first();

        if ($activeResearch) {
            $relocation->cancelled = true;
            $relocation->cancel_reason = 'Research in progress';
            $relocation->save();
            $this->warn('Relocation cancelled for planet ' . $planet->id . ': research in progress');
            return;
        }

        // Check 3: No active repairs
        $activeRepair = RepairQueue::where('planet_id', $planet->id)
            ->where('processed', 0)
            ->where('canceled', 0)
            ->first();

        if ($activeRepair) {
            $relocation->cancelled = true;
            $relocation->cancel_reason = 'Repairs in progress';
            $relocation->save();
            $this->warn('Relocation cancelled for planet ' . $planet->id . ': repairs in progress');
            return;
        }

        // Check 4: No active fleets from this planet
        $activeFleet = FleetMission::where('planet_id_from', $planet->id)
            ->where('processed', 0)
            ->first();

        if ($activeFleet) {
            $relocation->cancelled = true;
            $relocation->cancel_reason = 'Active fleet mission';
            $relocation->save();
            $this->warn('Relocation cancelled for planet ' . $planet->id . ': active fleet mission');
            return;
        }

        // Check 5: Target position is still free
        $targetOccupied = Planet::where([
            ['galaxy', $relocation->to_galaxy],
            ['system', $relocation->to_system],
            ['planet', $relocation->to_position],
            ['planet_type', PlanetType::Planet->value],
            ['destroyed', 0],
        ])
        ->where('id', '!=', $planet->id)
        ->first();

        if ($targetOccupied) {
            $relocation->cancelled = true;
            $relocation->cancel_reason = 'Target position is occupied';
            $relocation->save();
            $this->warn('Relocation cancelled for planet ' . $planet->id . ': target position occupied');
            return;
        }

        // Check 6: Player still has enough Dark Matter
        $user = User::find($relocation->user_id);
        if (!$user) {
            $relocation->cancelled = true;
            $relocation->cancel_reason = 'User not found';
            $relocation->save();
            $this->warn('Relocation cancelled for planet ' . $planet->id . ': user not found');
            return;
        }

        $dm_cost = 240000;
        if ($user->dark_matter < $dm_cost) {
            $relocation->cancelled = true;
            $relocation->cancel_reason = 'Insufficient Dark Matter';
            $relocation->save();
            $this->warn('Relocation cancelled for planet ' . $planet->id . ': insufficient DM');
            return;
        }

        // All checks passed - execute relocation!

        // Create planet service to access ships
        $planetService = $planetServiceFactory->make($planet->id);

        // Get all ships currently on the planet
        $stationedShips = $planetService->getShips();
        $totalShips = $stationedShips->getAmount();

        // If there are ships, launch them to new coordinates via deployment mission
        if ($totalShips > 0) {
            // Store old coordinates
            $oldCoordinate = new Coordinate(
                $relocation->from_galaxy,
                $relocation->from_system,
                $relocation->from_position
            );

            $newCoordinate = new Coordinate(
                $relocation->to_galaxy,
                $relocation->to_system,
                $relocation->to_position
            );

            // Remove ships from planet
            $planetService->removeUnits($stationedShips);

            // Create deployment mission from old coordinates to new coordinates
            // Mission type 4 = Deployment, speed 100%, no resources, no parent mission
            $fleetMission = $fleetMissionService->createNewFromPlanet(
                $planetService,
                $newCoordinate,
                PlanetType::Planet,
                4, // Deployment mission
                $stationedShips,
                new Resources(0, 0, 0, 0),
                1.0, // 100% speed (slowest ship will determine actual speed)
                0, // No holding time
                0 // No parent mission
            );

            $this->info('Launched ' . $totalShips . ' ship(s) to new coordinates (mission ID: ' . $fleetMission->id . ')');
        }

        // Find and move moon if exists
        $moon = Planet::where([
            ['galaxy', $relocation->from_galaxy],
            ['system', $relocation->from_system],
            ['planet', $relocation->from_position],
            ['planet_type', PlanetType::Moon->value],
            ['user_id', $relocation->user_id],
            ['destroyed', 0],
        ])->first();

        // Move planet
        $planet->galaxy = $relocation->to_galaxy;
        $planet->system = $relocation->to_system;
        $planet->planet = $relocation->to_position;
        $planet->save();

        // Move moon if exists
        if ($moon) {
            $moon->galaxy = $relocation->to_galaxy;
            $moon->system = $relocation->to_system;
            $moon->planet = $relocation->to_position;
            $moon->save();
            $this->info('Moved moon with planet ' . $planet->id);
        }

        // Update all incoming fleet missions to new coordinates
        // This handles enemy attacks, ACS defend, transports, deployments, etc.
        // Ensures other players can't cancel relocation by sending fleets
        $incomingFleets = FleetMission::where('galaxy_to', $relocation->from_galaxy)
            ->where('system_to', $relocation->from_system)
            ->where('position_to', $relocation->from_position)
            ->where('processed', 0)
            ->get();

        $updatedFleetCount = 0;
        foreach ($incomingFleets as $fleet) {
            // Update destination to new coordinates
            $fleet->galaxy_to = $relocation->to_galaxy;
            $fleet->system_to = $relocation->to_system;
            $fleet->position_to = $relocation->to_position;
            $fleet->save();
            $updatedFleetCount++;
        }

        if ($updatedFleetCount > 0) {
            $this->info('Updated ' . $updatedFleetCount . ' incoming fleet mission(s) to new coordinates');
        }

        // Deduct Dark Matter
        $user->dark_matter -= $dm_cost;
        $user->save();

        // Mark relocation as processed
        $relocation->processed = true;
        $relocation->save();

        $this->info('Successfully relocated planet ' . $planet->id . ' to ' .
            $relocation->to_galaxy . ':' . $relocation->to_system . ':' . $relocation->to_position);
    }
}
