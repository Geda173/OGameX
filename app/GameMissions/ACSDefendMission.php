<?php

namespace OGame\GameMissions;

use OGame\GameMissions\Abstracts\GameMission;
use OGame\GameMissions\Models\MissionPossibleStatus;
use OGame\GameObjects\Models\Units\UnitCollection;
use OGame\Models\Enums\PlanetType;
use OGame\Models\FleetMission;
use OGame\Models\Planet\Coordinate;
use OGame\Services\ACSService;
use OGame\Services\PlanetService;

class ACSDefendMission extends GameMission
{
    protected static string $name = 'ACS Defend';
    protected static int $typeId = 5;
    protected static bool $hasReturnMission = true;

    /**
     * Maximum hold time in hours
     */
    private const MAX_HOLD_HOURS = 32;

    /**
     * @inheritdoc
     */
    public function isMissionPossible(PlanetService $planet, Coordinate $targetCoordinate, PlanetType $targetType, UnitCollection $units): MissionPossibleStatus
    {
        \Log::debug('ACS Defend mission check', [
            'from_planet' => $planet->getPlanetCoordinates()->asString(),
            'from_player' => $planet->getPlayer()->getId(),
            'target_coords' => $targetCoordinate->asString(),
            'target_type' => $targetType->value,
        ]);

        // ACS Defend mission is only possible for planets and moons.
        if (!in_array($targetType, [PlanetType::Planet, PlanetType::Moon])) {
            \Log::debug('ACS Defend failed: target type not planet/moon');
            return new MissionPossibleStatus(false);
        }

        // If target planet does not exist, the mission is not possible.
        $targetPlanet = $this->planetServiceFactory->makeForCoordinate($targetCoordinate, true, $targetType);
        if ($targetPlanet === null) {
            \Log::debug('ACS Defend failed: target planet not found');
            return new MissionPossibleStatus(false);
        }

        \Log::debug('ACS Defend target found', [
            'target_player' => $targetPlanet->getPlayer()->getId(),
            'target_planet_name' => $targetPlanet->getPlanetName(),
        ]);

        // Cannot defend your own planet (use deployment for that)
        if ($planet->getPlayer()->equals($targetPlanet->getPlayer())) {
            \Log::debug('ACS Defend failed: cannot defend own planet');
            return new MissionPossibleStatus(false);
        }

        // Check if target player is buddy or alliance member
        $isBuddyOrAlliance = ACSService::isBuddyOrAllianceMember($planet->getPlayer()->getId(), $targetPlanet->getPlayer()->getId());
        \Log::debug('ACS Defend buddy check', [
            'is_buddy_or_alliance' => $isBuddyOrAlliance,
        ]);

        if (!$isBuddyOrAlliance) {
            \Log::debug('ACS Defend failed: not buddy or alliance member');
            return new MissionPossibleStatus(false);
        }

        // If mission from and to coordinates and types are the same, the mission is not possible.
        if ($planet->getPlanetCoordinates()->equals($targetCoordinate) && $planet->getPlanetType() === $targetType) {
            \Log::debug('ACS Defend failed: same coordinates');
            return new MissionPossibleStatus(false);
        }

        // Check if the target has an Alliance Depot.
        // For moons, also check the parent planet's Alliance Depot.
        $allianceDepotLevel = $this->getAllianceDepotLevel($targetPlanet);

        if ($allianceDepotLevel === 0) {
            \Log::debug('ACS Defend failed: no alliance depot');
            if ($targetType === PlanetType::Moon) {
                return new MissionPossibleStatus(false, __('The target moon or its parent planet must have an Alliance Depot to accept defending fleets.'));
            }
            return new MissionPossibleStatus(false, __('The target planet must have an Alliance Depot to accept defending fleets.'));
        }

        // If all checks pass, the mission is possible.
        \Log::debug('ACS Defend mission is POSSIBLE!');
        return new MissionPossibleStatus(true);
    }

    /**
     * Additional validation for ACS Defend mission to ensure enough deuterium for the mission.
     *
     * @inheritdoc
     */
    public function startMissionSanityChecks(PlanetService $planet, Coordinate $targetCoordinate, PlanetType $targetType, UnitCollection $units, \OGame\Models\Resources $resources): void
    {
        // First run the parent sanity checks (resources, units, fleet slots, mission possible).
        parent::startMissionSanityChecks($planet, $targetCoordinate, $targetType, $units, $resources);

        // Additional check: Ensure there's enough deuterium for the hold duration.
        // Note: At this point, we don't know the holding time yet (it comes from the request),
        // so this validation will be done when the mission starts. However, we can still
        // provide a helpful error message if there's no Alliance Depot.

        $targetPlanet = $this->planetServiceFactory->makeForCoordinate($targetCoordinate, true, $targetType);
        if ($targetPlanet === null) {
            throw new \Exception(__('Target planet not found.'));
        }

        $allianceDepotLevel = $this->getAllianceDepotLevel($targetPlanet);
        if ($allianceDepotLevel === 0) {
            if ($targetType === PlanetType::Moon) {
                throw new \Exception(__('The target moon or its parent planet must have an Alliance Depot to accept defending fleets.'));
            }
            throw new \Exception(__('The target planet must have an Alliance Depot to accept defending fleets.'));
        }
    }

    /**
     * Get the Alliance Depot level for a planet or moon.
     * For moons, also checks the parent planet's Alliance Depot.
     *
     * @param PlanetService $planet
     * @return int The Alliance Depot level (0 if no Alliance Depot)
     */
    private function getAllianceDepotLevel(PlanetService $planet): int
    {
        $allianceDepotLevel = $planet->getObjectLevel('alliance_depot');

        // If this is a moon and has no Alliance Depot, check the parent planet.
        if ($allianceDepotLevel === 0 && $planet->isMoon() && $planet->hasPlanet()) {
            try {
                $parentPlanet = $planet->planet();
                $allianceDepotLevel = $parentPlanet->getObjectLevel('alliance_depot');
            } catch (\RuntimeException $e) {
                // If parent planet doesn't exist, keep level at 0.
                $allianceDepotLevel = 0;
            }
        }

        return $allianceDepotLevel;
    }

    /**
     * Process arrival of defending fleet
     * Fleet arrives and hold duration completes - calculate consumption and return
     */
    protected function processArrival(FleetMission $mission): void
    {
        // Fleet has completed its hold duration
        // Calculate deuterium consumed during the hold period

        $originPlanet = $this->planetServiceFactory->make($mission->planet_id_from, true);

        // Load target planet - handle legacy missions where planet_id_to might be null
        if ($mission->planet_id_to) {
            $targetPlanet = $this->planetServiceFactory->make($mission->planet_id_to, true);
        } else {
            // Fallback: load by coordinates for legacy missions
            $targetCoords = new Coordinate($mission->galaxy_to, $mission->system_to, $mission->position_to);
            $targetPlanet = $this->planetServiceFactory->makeForCoordinate($targetCoords, true);
        }

        if (!$targetPlanet) {
            \Log::error('ACSDefendMission: Target planet not found', [
                'mission_id' => $mission->id,
                'planet_id_to' => $mission->planet_id_to,
                'coordinates' => $mission->galaxy_to . ':' . $mission->system_to . ':' . $mission->position_to,
            ]);
            // Mark as processed and skip
            $mission->processed = 1;
            $mission->save();
            return;
        }

        // Get hold duration in hours
        $holdDurationSeconds = $mission->time_holding ?? 0;
        $holdDurationHours = $holdDurationSeconds / 3600;

        // Calculate total deuterium consumed during hold
        $holdConsumptionService = new \OGame\Services\FleetHoldConsumptionService();
        $units = $this->fleetMissionService->getFleetUnits($mission);
        $totalConsumptionNeeded = $holdConsumptionService->calculateTotalConsumption($units, (int)$holdDurationHours);

        // Check if target planet has Alliance Depot (or parent planet if moon)
        $depotLevel = $this->getAllianceDepotLevel($targetPlanet);

        // Calculate Alliance Depot supply (20,000 deut/hour per level)
        $depotSupplyRate = 20000; // per hour per level
        $depotSupplyAvailable = $depotLevel * $depotSupplyRate * $holdDurationHours;

        // Get the planet that should provide the deuterium
        // If defending a moon and the moon has no Alliance Depot, use the parent planet
        $depotPlanet = $targetPlanet;
        if ($targetPlanet->isMoon() && $targetPlanet->getObjectLevel('alliance_depot') === 0 && $targetPlanet->hasPlanet()) {
            try {
                $depotPlanet = $targetPlanet->planet();
            } catch (\RuntimeException $e) {
                // If parent planet doesn't exist, use moon
                $depotPlanet = $targetPlanet;
            }
        }

        // Check how much deuterium the depot planet actually has
        $planetDeuterium = $depotPlanet->deuterium()->get();
        $depotSupplyUsed = min($depotSupplyAvailable, $planetDeuterium, $totalConsumptionNeeded);

        // Deduct depot supply from depot planet storage
        if ($depotSupplyUsed > 0) {
            $depotPlanet->deductResources(new \OGame\Models\Resources(0, 0, $depotSupplyUsed, 0));
        }

        // Calculate how much fleet cargo needs to cover
        $fleetCargoConsumption = max(0, $totalConsumptionNeeded - $depotSupplyUsed);

        // Deduct consumed deuterium from mission cargo
        $originalDeuterium = $mission->deuterium;
        $mission->deuterium = max(0, $originalDeuterium - $fleetCargoConsumption);

        \Log::debug('ACS Defend hold completed - deuterium consumed', [
            'mission_id' => $mission->id,
            'hold_hours' => $holdDurationHours,
            'total_consumption_needed' => $totalConsumptionNeeded,
            'depot_level' => $depotLevel,
            'depot_supply_available' => $depotSupplyAvailable,
            'depot_supply_used' => $depotSupplyUsed,
            'planet_deuterium_before' => $planetDeuterium,
            'fleet_cargo_original' => $originalDeuterium,
            'fleet_cargo_consumed' => $fleetCargoConsumption,
            'fleet_cargo_remaining' => $mission->deuterium,
        ]);

        // No messages sent here - fleet owner will receive return message when fleet arrives home
        // Host planet owner doesn't need notification when defending fleet leaves

        // Mark the arrival mission as processed
        $mission->processed = 1;
        $mission->save();

        // Create and start the return mission with remaining resources
        $this->startReturn($mission, new \OGame\Models\Resources(0, 0, 0, 0), $units);
    }

    /**
     * Process return of defending fleet
     * Fleet returns home after hold duration expires
     */
    protected function processReturn(FleetMission $mission): void
    {
        // For return missions, planet_id_to is where the fleet is returning TO (the original sender)
        $homePlanet = $this->planetServiceFactory->make($mission->planet_id_to, true);

        // Add units back to home planet
        $homePlanet->addUnits($this->fleetMissionService->getFleetUnits($mission));

        // Add any remaining resources back
        $return_resources = $this->fleetMissionService->getResources($mission);
        if ($return_resources->any()) {
            $homePlanet->addResources($return_resources);
        }

        // Send message to the fleet owner (not the planet they were defending)
        $this->sendFleetReturnMessage($mission, $homePlanet->getPlayer());

        // Mark the return mission as processed
        $mission->processed = 1;
        $mission->save();
    }

    /**
     * Get maximum hold time in hours
     */
    public static function getMaxHoldHours(): int
    {
        return self::MAX_HOLD_HOURS;
    }
}
