<?php

namespace OGame\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use OGame\Factories\PlanetServiceFactory;
use OGame\GameObjects\Models\Units\UnitCollection;
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

        // Create planet service
        $planetService = $planetServiceFactory->make($planet->id);

        // Get all ships currently on the planet by reading ship columns
        $stationedShips = new UnitCollection();
        $shipObjects = \OGame\Services\ObjectService::getShipObjects();

        foreach ($shipObjects as $shipObject) {
            $machineName = $shipObject->machine_name;
            $amount = $planet->$machineName ?? 0;

            if ($amount > 0) {
                $stationedShips->addUnit($shipObject, $amount);
            }
        }

        $totalShips = $stationedShips->getAmount();

        // Store ship data for later fleet creation (after planet moves)
        $shipFleetData = null;
        if ($totalShips > 0) {
            // Remove ships from planet before moving it
            $planetService->removeUnits($stationedShips, true);

            // Store data to create fleet mission after planet moves
            $shipFleetData = [
                'ships' => $stationedShips,
                'total' => $totalShips,
            ];

            $this->info('Removed ' . $totalShips . ' ship(s) from planet for relocation');
        }

        // Store old and new coordinates BEFORE planet moves (for fleet mission calculation)
        $oldGalaxy = $relocation->from_galaxy;
        $oldSystem = $relocation->from_system;
        $oldPosition = $relocation->from_position;
        $newGalaxy = $relocation->to_galaxy;
        $newSystem = $relocation->to_system;
        $newPosition = $relocation->to_position;

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

        // Now that planet has moved, create fleet mission for ships if any
        if ($shipFleetData !== null) {
            try {
                // Calculate distance manually using old coordinates (planet has already moved in DB)
                $diffGalaxy = abs($oldGalaxy - $newGalaxy);
                $diffSystem = abs($oldSystem - $newSystem);
                $diffPlanet = abs($oldPosition - $newPosition);

                // Distance calculation using same logic as FleetMissionService
                $settingsService = app(\OGame\Services\SettingsService::class);
                if ($diffGalaxy != 0) {
                    // Different galaxy - check for donut galaxy shortcut
                    $diff2 = abs($diffGalaxy - $settingsService->numberOfGalaxies());
                    $distance = ($diff2 < $diffGalaxy) ? ($diff2 * 20000) : ($diffGalaxy * 20000);
                } elseif ($diffSystem != 0) {
                    // Different system - check for donut system shortcut
                    $diff2 = abs($diffSystem - 499);
                    $deltaSystem = ($diff2 < $diffSystem) ? $diff2 : $diffSystem;
                    $deltaSystem = max($deltaSystem, 1);
                    $distance = $deltaSystem * 5 * 19 + 2700;
                } elseif ($diffPlanet != 0) {
                    // Different planet position
                    $distance = $diffPlanet * 5 + 1000;
                } else {
                    // Same coordinates (shouldn't happen, but just in case)
                    $distance = 5;
                }

                // Calculate flight time: (35000 / speed_percent * sqrt(distance * 10 / slowest_speed) + 10) / game_speed
                $player = $planetService->getPlayer();
                $slowestSpeed = $shipFleetData['ships']->getSlowestUnitSpeed($player);
                $speedPercent = 10; // 100% speed (OGame uses 1-10 scale, where 10 = 100%)
                $fleetSpeed = $settingsService->fleetSpeed();
                $travelTime = (int)max(
                    round((35000 / $speedPercent * sqrt($distance * 10 / $slowestSpeed) + 10) / $fleetSpeed),
                    1
                );

                $currentTime = time();
                $arrivalTime = $currentTime + $travelTime;

                // Manually create fleet mission record
                $fleetMission = new FleetMission();
                $fleetMission->user_id = $user->id;
                $fleetMission->planet_id_from = $planet->id;
                $fleetMission->planet_id_to = $planet->id; // Same planet, just moved
                $fleetMission->galaxy_from = $oldGalaxy;
                $fleetMission->system_from = $oldSystem;
                $fleetMission->position_from = $oldPosition;
                $fleetMission->type_from = PlanetType::Planet->value;
                $fleetMission->galaxy_to = $newGalaxy;
                $fleetMission->system_to = $newSystem;
                $fleetMission->position_to = $newPosition;
                $fleetMission->type_to = PlanetType::Planet->value;
                $fleetMission->mission_type = 4; // Deployment
                $fleetMission->time_departure = $currentTime;
                $fleetMission->time_arrival = $arrivalTime;

                // Add ships to mission
                foreach ($shipFleetData['ships']->units as $unit) {
                    $machineName = $unit->unitObject->machine_name;
                    $fleetMission->$machineName = $unit->amount;
                }

                // Resources (none for relocation)
                $fleetMission->metal = 0;
                $fleetMission->crystal = 0;
                $fleetMission->deuterium = 0;

                $fleetMission->processed = 0;
                $fleetMission->save();

                $this->info('Launched ' . $shipFleetData['total'] . ' ship(s) to new coordinates (mission ID: ' . $fleetMission->id . ', arrival in ' . gmdate('H:i:s', $travelTime) . ')');
            } catch (\Exception $e) {
                $this->warn('Could not launch ships to new coordinates: ' . $e->getMessage());
                $this->warn('Ships were removed from planet but could not be sent. They are lost!');
            }
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
