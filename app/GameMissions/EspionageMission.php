<?php

namespace OGame\GameMissions;

use OGame\GameMissions\Abstracts\GameMission;
use OGame\GameMissions\BattleEngine\Models\BattleResult;
use OGame\GameMissions\Models\MissionPossibleStatus;
use OGame\GameObjects\Models\Units\UnitCollection;
use OGame\Models\Enums\PlanetType;
use OGame\Models\EspionageReport;
use OGame\Models\FleetMission;
use OGame\Models\Planet\Coordinate;
use OGame\Services\DebrisFieldService;
use OGame\Services\PlanetService;
use OGame\Services\PlayerService;
use Throwable;

class EspionageMission extends GameMission
{
    protected static string $name = 'Espionage';
    protected static int $typeId = 6;
    protected static bool $hasReturnMission = true;

    /**
     * @inheritdoc
     */
    public function isMissionPossible(PlanetService $planet, Coordinate $targetCoordinate, PlanetType $targetType, UnitCollection $units): MissionPossibleStatus
    {
        // Espionage mission is only possible for planets and moons.
        if (!in_array($targetType, [PlanetType::Planet, PlanetType::Moon])) {
            return new MissionPossibleStatus(false);
        }

        // If target planet does not exist, the mission is not possible.
        // Force reload from database (useCache=false) to ensure we get the latest planet state
        // This is important for destroyed planets which may have been recently abandoned
        $targetPlanet = $this->planetServiceFactory->makeForCoordinate($targetCoordinate, false, $targetType);
        if ($targetPlanet === null) {
            return new MissionPossibleStatus(false);
        }

        // If planet belongs to current player, the mission is not possible.
        // Allow spying on destroyed planets (which have null player)
        if ($targetPlanet->getPlayer() !== null && $planet->getPlayer()->equals($targetPlanet->getPlayer())) {
            return new MissionPossibleStatus(false);
        }

        // If no espionage probes are present in the fleet, the mission is not possible.
        if ($units->getAmountByMachineName('espionage_probe') === 0) {
            return new MissionPossibleStatus(false);
        }

        // If mission from and to coordinates and types are the same, the mission is not possible.
        if ($planet->getPlanetCoordinates()->equals($targetCoordinate) && $planet->getPlanetType() === $targetType) {
            return new MissionPossibleStatus(false);
        }

        // Check noob protection: strong players cannot spy on protected players
        $spy = $planet->getPlayer();
        $target = $targetPlanet->getPlayer();

        // Skip noob protection check for destroyed planets (which have null player)
        if ($target !== null && $target->isNewbie($spy)) {
            return new MissionPossibleStatus(false, __('The target player is under noob protection and cannot be spied on.'));
        }

        // If all checks pass, the mission is possible.
        return new MissionPossibleStatus(true);
    }

    /**
     * @inheritdoc
     * @throws Throwable
     */
    protected function processArrival(FleetMission $mission): void
    {
        $target_planet = $this->planetServiceFactory->make($mission->planet_id_to, true);
        $origin_planet = $this->planetServiceFactory->make($mission->planet_id_from, true);

        // Trigger target planet update to make sure the espionage report is accurate.
        $target_planet->update();

        $spy = $origin_planet->getPlayer();
        $target = $target_planet->getPlayer();

        // Check if spy should become outlaw (vogelfrei)
        // This happens when a player under noob protection spies on a strong player
        if ($spy->isNewbie($target) && $target->isStrong($spy)) {
            $spy->makeOutlaw();
        }

        // Calculate counter-espionage chance
        $counterEspionageChance = $this->calculateCounterEspionageChance($mission, $origin_planet, $target_planet);

        $reportId = $this->createEspionageReport($mission, $origin_planet, $target_planet, $counterEspionageChance);

        // --- Defender notification (mirror official OGame "you were spied" message)
        // Defender is the owner of the target planet
        // Only send notification if the target planet has a player (not destroyed/abandoned)
        $defenderPlayer = $target_planet->getPlayer();
        if ($defenderPlayer !== null) {
            $defenderUserId = $defenderPlayer->getId();

            if ($defenderUserId) {
                // Params expected by t_messages.espionage_detected.*
                $attackerName = $origin_planet->getPlayer()->getUsername();

                $params = [
// IMPORTANT: pass the raw mission planet id inside [planet]...[/planet]
                    'planet'        => '[planet]' . $mission->planet_id_from . '[/planet]',
                    'attacker_name' => $attackerName,

// NEW:
        'defender'       => '[planet]' . $mission->planet_id_to . '[/planet]',   // defender planet
        'defender_name'  => $defenderPlayer->getUsername(),          // defender player name

                    'chance'        => $counterEspionageChance,
                ];

                $playerServiceFactory = resolve(\OGame\Factories\PlayerServiceFactory::class);
                $defenderService      = $playerServiceFactory->make($defenderUserId);

                (new \OGame\Services\MessageService($defenderService))
                    ->sendSystemMessageToPlayer(
                        $defenderService,
                        \OGame\GameMessages\DefenderEspionageDetected::class,
                        $params
                    );
            }
        }

        // Send a message to the player with a reference to the espionage report.
        $this->messageService->sendEspionageReportMessageToPlayer(
            $origin_planet->getPlayer(),
            $reportId
        );

        // Mark the arrival mission as processed
        $mission->processed = 1;
        $mission->save();

        // Assembly new unit collection.
        $units = $this->fleetMissionService->getFleetUnits($mission);

        // Counter-espionage: check if defending fleet destroys attacking probes
        $units = $this->processCounterEspionage($mission, $origin_planet, $target_planet, $units, $counterEspionageChance);

        // Create and start the return mission.
        $this->startReturn($mission, $this->fleetMissionService->getResources($mission), $units);
    }

    /**
     * @inheritdoc
     */
    protected function processReturn(FleetMission $mission): void
    {
        // Load the target planet
        $target_planet = $this->planetServiceFactory->make($mission->planet_id_to, true);

        // Espionage return trip: add back the units to the source planet. Then we're done.
        $target_planet->addUnits($this->fleetMissionService->getFleetUnits($mission));

        // Add resources to the origin planet (if any).
        $return_resources = $this->fleetMissionService->getResources($mission);
        if ($return_resources->any()) {
            $target_planet->addResources($return_resources);
        }

        // Espionage return mission does not send a return confirmation message to the user.

        // Mark the return mission as processed
        $mission->processed = 1;
        $mission->save();
    }

    /**
     * Creates an espionage report for the target planet.
     *
     * @param FleetMission $mission
     * @param PlanetService $originPlanet
     * @param PlanetService $targetPlanet
     * @param int $counterEspionageChance
     * @return int
     */
    private function createEspionageReport(FleetMission $mission, PlanetService $originPlanet, PlanetService $targetPlanet, int $counterEspionageChance): int
    {
        // Create new espionage report record.
        $report = new EspionageReport();
        $report->planet_galaxy = $targetPlanet->getPlanetCoordinates()->galaxy;
        $report->planet_system = $targetPlanet->getPlanetCoordinates()->system;
        $report->planet_position = $targetPlanet->getPlanetCoordinates()->position;
        $report->planet_type = $targetPlanet->getPlanetType()->value;

        // Handle destroyed planets with no player
        $defenderPlayer = $targetPlanet->getPlayer();
        $report->planet_user_id = $defenderPlayer !== null ? $defenderPlayer->getId() : null;
        $report->counter_espionage_chance = $counterEspionageChance;

        $report->player_info = [
            'player_id' => $defenderPlayer !== null ? (string)$defenderPlayer->getId() : null,
            'player_name' => $defenderPlayer !== null ? $defenderPlayer->getUsername() : __('Destroyed Planet'),
        ];

        // Resources
        $report->resources = [
            'metal' => (int)$targetPlanet->metal()->get(),
            'crystal' => (int)$targetPlanet->crystal()->get(),
            'deuterium' => (int)$targetPlanet->deuterium()->get(),
            'energy' => (int)$targetPlanet->energy()->get()
        ];

        // Debris field (if any).
        $debrisField = resolve(DebrisFieldService::class);
        $debrisField->loadOrCreateForCoordinates($targetPlanet->getPlanetCoordinates());
        $debrisFieldResources = $debrisField->getResources();
        if ($debrisFieldResources->any()) {
            $report->debris = [
                'metal' => (int)$debrisFieldResources->metal->get(),
                'crystal' => (int)$debrisFieldResources->crystal->get(),
                'deuterium' => (int)$debrisFieldResources->deuterium->get(),
            ];
        }

        // TODO: Validate this does not cause issues when probing slot 16
        $attackerEspionageLevel = $originPlanet->getPlayer()->getResearchLevel('espionage_technology');
        // Destroyed planets have no player, so espionage level is 0
        $defenderEspionageLevel = $defenderPlayer !== null ? $defenderPlayer->getResearchLevel('espionage_technology') : 0;
        $techDifference = $defenderEspionageLevel - $attackerEspionageLevel;
        $levelDifference = max(0, $techDifference);
        $extraProbesRequired = pow($levelDifference, 2);
        $remainingProbes = max(0, $mission->espionage_probe - $extraProbesRequired);

        // Fleets
        if ($this->canRevealData($remainingProbes, $attackerEspionageLevel, $defenderEspionageLevel, 2, 1)) {
            // Get planet's own ships
            $allShips = $targetPlanet->getShipUnits();

            // Add ACS Defend mission ships that are currently holding at this planet
            $defendingMissions = $this->fleetMissionService->getDefendingMissionsAtPlanet($targetPlanet->getPlanetId());
            foreach ($defendingMissions as $defendingMission) {
                $defendingUnits = $this->fleetMissionService->getFleetUnits($defendingMission);
                $allShips->addCollection($defendingUnits);
            }

            $report->ships = $allShips->toArray();
        }

        // Defense
        if ($this->canRevealData($remainingProbes, $attackerEspionageLevel, $defenderEspionageLevel, 3, 2)) {
            $report->defense = $targetPlanet->getDefenseUnits()->toArray();
        }

        // Buildings
        if ($this->canRevealData($remainingProbes, $attackerEspionageLevel, $defenderEspionageLevel, 5, 3)) {
            $report->buildings = $targetPlanet->getBuildingArray();
        }

        // Research (only if planet has a player)
        if ($defenderPlayer !== null && $this->canRevealData($remainingProbes, $attackerEspionageLevel, $defenderEspionageLevel, 7, 4)) {
            $report->research = $defenderPlayer->getResearchArray();
        }

        $report->save();

        return $report->id;
    }

    /**
     * Determine if specific data can be revealed in the report based on remaining probes and espionage levels.
     *
     * @param int $remainingProbes
     * @param int $attackerLevel
     * @param int $defenderLevel
     * @param int $probeThreshold
     * @param int $levelThreshold
     * @return bool
     */
    private function canRevealData(int $remainingProbes, int $attackerLevel, int $defenderLevel, int $probeThreshold, int $levelThreshold): bool
    {
        return $remainingProbes >= $probeThreshold || $attackerLevel - $levelThreshold >= $defenderLevel;
    }

    /**
     * Calculate the counter-espionage chance percentage.
     * Formula from OGame wiki: 2^(E-O) * S * F * 0.25%
     *
     * Where:
     * - E = Enemy's (defender's) espionage technology level
     * - O = Owner's (attacker's) espionage technology level
     * - S = Number of probes sent by attacker
     * - F = Defender's fleet size (total number of ships)
     *
     * @param FleetMission $mission
     * @param PlanetService $attackerPlanet
     * @param PlanetService $defenderPlanet
     * @return int Counter-espionage chance as integer percentage (0-100)
     */
    private function calculateCounterEspionageChance(FleetMission $mission, PlanetService $attackerPlanet, PlanetService $defenderPlanet): int
    {
        // Get espionage technology levels
        // Destroyed planets have no player, so espionage level is 0
        $defenderPlayer = $defenderPlanet->getPlayer();
        $defenderEspionageLevel = $defenderPlayer !== null ? $defenderPlayer->getResearchLevel('espionage_technology') : 0;
        $attackerEspionageLevel = $attackerPlanet->getPlayer()->getResearchLevel('espionage_technology');

        // Number of probes sent by attacker
        $probesSent = $mission->espionage_probe;

        // Defender's total fleet size (all ships on the planet + ACS Defend missions)
        $defenderShips = $defenderPlanet->getShipUnits();

        // Add ACS Defend mission ships that are currently holding at this planet
        $defendingMissions = $this->fleetMissionService->getDefendingMissionsAtPlanet($defenderPlanet->getPlanetId());
        foreach ($defendingMissions as $defendingMission) {
            $defendingUnits = $this->fleetMissionService->getFleetUnits($defendingMission);
            $defenderShips->addCollection($defendingUnits);
        }

        $defenderFleetSize = $defenderShips->getAmount();

        // Apply the formula: 2^(E-O) * S * F * 0.25%
        $techDifference = $defenderEspionageLevel - $attackerEspionageLevel;
        $baseProbability = pow(2, $techDifference) * $probesSent * $defenderFleetSize * 0.0025;

        // Cap at 100% maximum
        $chance = min(100, (int)round($baseProbability));

        return max(0, $chance);
    }

    /**
     * Process counter-espionage: determine if it triggers and execute battle if so.
     * Note: Even if probes are destroyed, the spy report has already been sent (per OGame rules).
     *
     * @param FleetMission $mission
     * @param PlanetService $attackerPlanet
     * @param PlanetService $defenderPlanet
     * @param UnitCollection $attackerUnits
     * @param int $counterEspionageChance
     * @return UnitCollection Updated attacker units after potential battle
     * @throws Throwable
     */
    private function processCounterEspionage(
        FleetMission $mission,
        PlanetService $attackerPlanet,
        PlanetService $defenderPlanet,
        UnitCollection $attackerUnits,
        int $counterEspionageChance
    ): UnitCollection {
        // Get defender's total fleet size (planet ships + ACS Defend missions)
        $defenderShips = $defenderPlanet->getShipUnits();

        // Add ACS Defend mission ships
        $defendingMissions = $this->fleetMissionService->getDefendingMissionsAtPlanet($defenderPlanet->getPlanetId());
        foreach ($defendingMissions as $defendingMission) {
            $defendingUnits = $this->fleetMissionService->getFleetUnits($defendingMission);
            $defenderShips->addCollection($defendingUnits);
        }

        $defenderFleetAmount = $defenderShips->getAmount();

        // If no chance or no defender ships, no counter-espionage possible
        if ($counterEspionageChance <= 0 || $defenderFleetAmount === 0) {
            return $attackerUnits;
        }

        // Roll for counter-espionage trigger (random factor)
        $roll = rand(1, 100);

        if ($roll > $counterEspionageChance) {
            // Counter-espionage did not trigger
            return $attackerUnits;
        }

        // Counter-espionage triggered! Execute battle between defender's fleet and attacker's probes
        $attackerPlayer = $attackerPlanet->getPlayer();

        // Use configured battle engine
        switch ($this->settings->battleEngine()) {
            case 'php':
                $battleEngine = new \OGame\GameMissions\BattleEngine\PhpBattleEngine(
                    $attackerUnits,
                    $attackerPlayer,
                    $defenderPlanet,
                    $this->settings
                );
                break;
            case 'rust':
            default:
                $battleEngine = new \OGame\GameMissions\BattleEngine\RustBattleEngine(
                    $attackerUnits,
                    $attackerPlayer,
                    $defenderPlanet,
                    $this->settings
                );
                break;
        }

        $battleResult = $battleEngine->simulateBattle();

        // Calculate units lost
        $defenderUnitsLost = clone $battleResult->defenderUnitsStart;
        $defenderUnitsLost->subtractCollection($battleResult->defenderUnitsResult);

        // Deduct defender's lost units from the planet (if any)
        if ($defenderUnitsLost->getAmount() > 0) {
            $defenderPlanet->removeUnits($defenderUnitsLost, false);
        }

        // Save defender planet
        $defenderPlanet->save();

        // Create debris field from destroyed ships
        $debrisFieldService = resolve(DebrisFieldService::class);
        $debrisFieldService->loadOrCreateForCoordinates($defenderPlanet->getPlanetCoordinates());
        $debrisFieldService->appendResources($battleResult->debris);
        $debrisFieldService->save();

        // Create battle report
        $reportId = $this->createCounterEspionageBattleReport($attackerPlayer, $defenderPlanet, $battleResult);

        // Check if probes were destroyed in first round
        $probesDestroyedFirstRound = false;
        if (count($battleResult->rounds) > 0) {
            $firstRound = $battleResult->rounds[0];
            if ($firstRound->attackerShips->getAmount() === 0) {
                $probesDestroyedFirstRound = true;
            }
        }

        // Send appropriate messages
        if ($probesDestroyedFirstRound) {
            // Send simplified "fleet lost contact" message to spy (no fleet or tech info)
            $coordinates = '[coordinates]' . $defenderPlanet->getPlanetCoordinates()->asString() . '[/coordinates]';
            $spyPlayer = $attackerPlanet->getPlayer();
            $messageService = new \OGame\Services\MessageService($spyPlayer);
            $messageService->sendSystemMessageToPlayer($spyPlayer, \OGame\GameMessages\FleetLostContact::class, [
                'coordinates' => $coordinates,
            ]);
        } else {
            // Normal: send full battle report to spy
            $spyPlayer = $attackerPlanet->getPlayer();
            $messageService = new \OGame\Services\MessageService($spyPlayer);
            $messageService->sendBattleReportMessageToPlayer($spyPlayer, $reportId);
        }

        // Always send full battle report to defender (planet owner)
        // Only send if the planet has a player (not destroyed/abandoned)
        $defenderPlayer = $defenderPlanet->getPlayer();
        if ($defenderPlayer !== null) {
            $defenderMessageService = new \OGame\Services\MessageService($defenderPlayer);
            $defenderMessageService->sendBattleReportMessageToPlayer($defenderPlayer, $reportId);
        }

        // Return the surviving attacker units (probes that survived counter-espionage)
        return $battleResult->attackerUnitsResult;
    }

    /**
     * Create a battle report for a counter-espionage battle.
     *
     * @param PlayerService $spyPlayer The player who sent the espionage probes (attacker in battle)
     * @param PlanetService $defenderPlanet The planet that was being spied on
     * @param BattleResult $battleResult The result of the battle
     * @return int The ID of the created battle report
     */
    private function createCounterEspionageBattleReport(PlayerService $spyPlayer, PlanetService $defenderPlanet, BattleResult $battleResult): int
    {
        // Create new battle report record
        $report = new \OGame\Models\BattleReport();
        $report->planet_galaxy = $defenderPlanet->getPlanetCoordinates()->galaxy;
        $report->planet_system = $defenderPlanet->getPlanetCoordinates()->system;
        $report->planet_position = $defenderPlanet->getPlanetCoordinates()->position;
        $report->planet_type = $defenderPlanet->getPlanetType()->value;
        $defenderPlayer = $defenderPlanet->getPlayer();
        $report->planet_user_id = $defenderPlayer !== null ? $defenderPlayer->getId() : null;

        $report->general = [
            'moon_existed' => $battleResult->moonExisted,
            'moon_chance' => $battleResult->moonChance,
            'moon_created' => $battleResult->moonCreated,
        ];

        // In counter-espionage: spy's probes are "attacker", defender's fleet is "defender"
        $report->attacker = [
            'player_id' => $spyPlayer->getId(),
            'resource_loss' => $battleResult->attackerResourceLoss->sum(),
            'units' => $battleResult->attackerUnitsStart->toArray(),
            'weapon_technology' => $battleResult->attackerWeaponLevel,
            'shielding_technology' => $battleResult->attackerShieldLevel,
            'armor_technology' => $battleResult->attackerArmorLevel,
        ];

        $report->defender = [
            'player_id' => $defenderPlayer !== null ? $defenderPlayer->getId() : null,
            'resource_loss' => $battleResult->defenderResourceLoss->sum(),
            'units' => $battleResult->defenderUnitsStart->toArray(),
            'weapon_technology' => $battleResult->defenderWeaponLevel,
            'shielding_technology' => $battleResult->defenderShieldLevel,
            'armor_technology' => $battleResult->defenderArmorLevel,
        ];

        // No loot in counter-espionage
        $report->loot = [
            'percentage' => 0,
            'metal' => 0,
            'crystal' => 0,
            'deuterium' => 0,
        ];

        $report->debris = [
            'metal' => $battleResult->debris->metal->get(),
            'crystal' => $battleResult->debris->crystal->get(),
            'deuterium' => $battleResult->debris->deuterium->get(),
        ];

        // No defenses are repaired in counter-espionage (only ships fight, no defense structures)
        $report->repaired_defenses = [];

        $rounds = [];
        foreach ($battleResult->rounds as $round) {
            $rounds[] = [
                'attacker_ships' => $round->attackerShips->toArray(),
                'defender_ships' => $round->defenderShips->toArray(),
                'attacker_losses' => $round->attackerLosses->toArray(),
                'defender_losses' => $round->defenderLosses->toArray(),
                'attacker_losses_in_this_round' => $round->attackerLossesInRound->toArray(),
                'defender_losses_in_this_round' => $round->defenderLossesInRound->toArray(),
                'absorbed_damage_attacker' => $round->absorbedDamageAttacker,
                'absorbed_damage_defender' => $round->absorbedDamageDefender,
                'full_strength_attacker' => $round->fullStrengthAttacker,
                'full_strength_defender' => $round->fullStrengthDefender,
                'hits_attacker' => $round->hitsAttacker,
                'hits_defender' => $round->hitsDefender,
            ];
        }

        $report->rounds = $rounds;
        $report->save();

        return $report->id;
    }
}
