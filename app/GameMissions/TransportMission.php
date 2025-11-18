<?php

namespace OGame\GameMissions;

use OGame\GameMessages\TransportArrived;
use OGame\GameMessages\TransportReceived;
use OGame\GameMissions\Abstracts\GameMission;
use OGame\GameMissions\Models\MissionPossibleStatus;
use OGame\GameObjects\Models\Units\UnitCollection;
use OGame\Models\Enums\PlanetType;
use OGame\Models\FleetMission;
use OGame\Models\Planet\Coordinate;
use OGame\Models\Resources;
use OGame\Services\PlanetService;

class TransportMission extends GameMission
{
    protected static string $name = 'Transport';
    protected static int $typeId = 3;
    protected static bool $hasReturnMission = true;

    /**
     * @inheritdoc
     */
    public function isMissionPossible(PlanetService $planet, Coordinate $targetCoordinate, PlanetType $targetType, UnitCollection $units): MissionPossibleStatus
    {
        // Transport mission is only possible for planets and moons.
        if (!in_array($targetType, [PlanetType::Planet, PlanetType::Moon])) {
            return new MissionPossibleStatus(false);
        }

        // If target planet does not exist, the mission is not possible.
        $targetPlanet = $this->planetServiceFactory->makeForCoordinate($targetCoordinate, true, $targetType);
        if ($targetPlanet === null) {
            return new MissionPossibleStatus(false);
        }

        // Transport missions to destroyed planets (no owner) are not possible
        if ($targetPlanet->getPlayer() === null) {
            return new MissionPossibleStatus(false);
        }

        // If mission from and to coordinates and types are the same, the mission is not possible.
        if ($planet->getPlanetCoordinates()->equals($targetCoordinate) && $planet->getPlanetType() === $targetType) {
            return new MissionPossibleStatus(false);
        }

        // If all checks pass, the mission is possible.
        return new MissionPossibleStatus(true);
    }

    /**
     * @inheritdoc
     */
    protected function processArrival(FleetMission $mission): void
    {
        $origin_planet = $this->planetServiceFactory->make($mission->planet_id_from, true);
        $target_planet = $this->planetServiceFactory->make($mission->planet_id_to, true);

        $originPlayer = $origin_planet->getPlayer();
        $targetPlayer = $target_planet->getPlayer();

        // If origin planet was abandoned, cancel mission and don't create return
        if ($originPlayer === null) {
            \Log::info('Transport mission cancelled: origin planet was abandoned', [
                'mission_id' => $mission->id,
                'origin_planet' => $mission->planet_id_from,
            ]);
            $mission->processed = 1;
            $mission->save();
            return;
        }

        // If target planet was destroyed/abandoned, resources are lost, but fleet returns
        if ($targetPlayer === null) {
            \Log::info('Transport mission failed: target planet was destroyed', [
                'mission_id' => $mission->id,
                'target_planet' => $mission->planet_id_to,
            ]);

            // Send message to origin player about failed transport
            $this->messageService->sendSystemMessageToPlayer($originPlayer, TransportArrived::class, [
                'from' => '[planet]' . $mission->planet_id_from . '[/planet]',
                'to' => '[planet]' . $mission->planet_id_to . '[/planet]',
                'metal' => '0',
                'crystal' => '0',
                'deuterium' => '0',
            ]);

            $mission->processed = 1;
            $mission->save();

            // Create return mission with empty cargo (resources lost)
            $units = $this->fleetMissionService->getFleetUnits($mission);
            $this->startReturn($mission, new Resources(0, 0, 0, 0), $units);
            return;
        }

        // Add resources to the target planet
        $target_planet->addResources($this->fleetMissionService->getResources($mission));

        // Send a message to the origin player that the mission has arrived
        $this->messageService->sendSystemMessageToPlayer($originPlayer, TransportArrived::class, [
            'from' => '[planet]' . $mission->planet_id_from . '[/planet]',
            'to' => '[planet]' . $mission->planet_id_to . '[/planet]',
            'metal' => (string)$mission->metal,
            'crystal' => (string)$mission->crystal,
            'deuterium' => (string)$mission->deuterium,
        ]);

        if ($originPlayer->getId() !== $targetPlayer->getId()) {
            // Send a message to the target player that the mission has arrived
            $this->messageService->sendSystemMessageToPlayer($targetPlayer, TransportReceived::class, [
                'from' => '[planet]' . $mission->planet_id_from . '[/planet]',
                'to' => '[planet]' . $mission->planet_id_to . '[/planet]',
                'metal' => (string)$mission->metal,
                'crystal' => (string)$mission->crystal,
                'deuterium' => (string)$mission->deuterium,
            ]);
        }

        // Mark the arrival mission as processed
        $mission->processed = 1;
        $mission->save();

        // Create and start the return mission.
        $units = $this->fleetMissionService->getFleetUnits($mission);
        $this->startReturn($mission, new Resources(0, 0, 0, 0), $units);
    }

    /**
     * @inheritdoc
     */
    protected function processReturn(FleetMission $mission): void
    {
        // Load the target planet
        $target_planet = $this->planetServiceFactory->make($mission->planet_id_to, true);

        // Transport return trip: add back the units to the source planet.
        $target_planet->addUnits($this->fleetMissionService->getFleetUnits($mission));

        // Add resources to the origin planet (if any).
        $return_resources = $this->fleetMissionService->getResources($mission);
        if ($return_resources->any()) {
            $target_planet->addResources($return_resources);
        }

        // Send message to player that the return mission has arrived.
        $this->sendFleetReturnMessage($mission, $target_planet->getPlayer());

        // Mark the return mission as processed
        $mission->processed = 1;
        $mission->save();
    }
}
