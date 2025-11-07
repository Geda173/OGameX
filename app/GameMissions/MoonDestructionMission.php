<?php

namespace OGame\GameMissions;

use OGame\GameMissions\Abstracts\GameMission;
use OGame\GameMissions\BattleEngine\Models\BattleResult;
use OGame\GameMissions\BattleEngine\PhpBattleEngine;
use OGame\GameMissions\BattleEngine\RustBattleEngine;
use OGame\GameMissions\Models\MissionPossibleStatus;
use OGame\GameObjects\Models\Units\UnitCollection;
use OGame\Models\BattleReport;
use OGame\Models\Enums\PlanetType;
use OGame\Models\FleetMission;
use OGame\Models\Planet\Coordinate;
use OGame\Services\DebrisFieldService;
use OGame\Services\ObjectService;
use OGame\Services\PlanetService;
use OGame\Services\PlayerService;
use Throwable;

class MoonDestructionMission extends GameMission
{
    protected static string $name = 'Moon Destruction';
    protected static int $typeId = 9;
    protected static bool $hasReturnMission = true;

    /**
     * @inheritdoc
     */
    public function isMissionPossible(PlanetService $planet, Coordinate $targetCoordinate, PlanetType $targetType, UnitCollection $units): MissionPossibleStatus
    {
        // Moon destruction mission is only possible for moons.
        if ($targetType !== PlanetType::Moon) {
            return new MissionPossibleStatus(false);
        }

        // If target moon does not exist, the mission is not possible.
        $targetPlanet = $this->planetServiceFactory->makeForCoordinate($targetCoordinate, true, $targetType);
        if ($targetPlanet === null) {
            return new MissionPossibleStatus(false);
        }

        // If moon belongs to current player, the mission is not possible.
        if ($planet->getPlayer()->equals($targetPlanet->getPlayer())) {
            return new MissionPossibleStatus(false);
        }

        // If mission from and to coordinates and types are the same, the mission is not possible.
        if ($planet->getPlanetCoordinates()->equals($targetCoordinate) && $planet->getPlanetType() === $targetType) {
            return new MissionPossibleStatus(false);
        }

        // Check if the fleet contains at least one Death Star
        if ($units->getAmountByMachineName('deathstar') < 1) {
            return new MissionPossibleStatus(false);
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
        $defenderMoon = $this->planetServiceFactory->make($mission->planet_id_to, true);
        $origin_planet = $this->planetServiceFactory->make($mission->planet_id_from, true);

        // Check if moon still exists
        if ($defenderMoon === null) {
            // Moon was already destroyed (e.g., planet abandoned)
            $this->sendMoonNotFoundMessage($mission, $origin_planet->getPlayer());

            // Mark the arrival mission as processed
            $mission->processed = 1;
            $mission->save();

            // Start return mission with no units
            $this->startReturn($mission, $this->fleetMissionService->getResources($mission), new UnitCollection());
            return;
        }

        // Trigger defender moon update to make sure the battle uses up-to-date info.
        $defenderMoon->update();

        $attackerPlayer = $origin_planet->getPlayer();
        $attackerUnits = $this->fleetMissionService->getFleetUnits($mission);

        // Get moon size in km (convert from fields)
        $moonSizeKm = $defenderMoon->getPlanetDiameter();

        // Calculate chances BEFORE battle
        $deathStarCount = $attackerUnits->getAmountByMachineName('deathstar');
        $moonDestructionChance = $this->calculateMoonDestructionChance($moonSizeKm, $deathStarCount);
        $deathStarDestructionChance = $this->calculateDeathStarDestructionChance($moonSizeKm);

        // Execute the battle logic using configured battle engine
        switch ($this->settings->battleEngine()) {
            case 'php':
                $battleEngine = new PhpBattleEngine($attackerUnits, $attackerPlayer, $defenderMoon, $this->settings);
                break;
            case 'rust':
            default:
                // Default to RustBattleEngine if no specific engine is configured
                $battleEngine = new RustBattleEngine($attackerUnits, $attackerPlayer, $defenderMoon, $this->settings);
                break;
        }

        $battleResult = $battleEngine->simulateBattle();

        // Deduct defender's lost units from the defenders moon.
        $defenderUnitsLost = clone $battleResult->defenderUnitsStart;
        $defenderUnitsLost->subtractCollection($battleResult->defenderUnitsResult);
        $defenderMoon->removeUnits($defenderUnitsLost, false);

        // Calculate repaired defenses (70% chance for each destroyed defense structure)
        $repairedDefenses = $this->calculateRepairedDefenses($defenderUnitsLost);

        // Add repaired defenses back to the moon
        if ($repairedDefenses->getAmount() > 0) {
            $defenderMoon->addUnits($repairedDefenses, false);
        }

        // Save defenders moon
        $defenderMoon->save();

        // Handle ACS Defend missions that participated in the battle
        // Calculate survival rate for each unit type and distribute survivors back to defending missions
        $acsDefendTotalStart = new UnitCollection();
        foreach ($battleResult->defendingMissions as $defendingMission) {
            $defendingUnits = $this->fleetMissionService->getFleetUnits($defendingMission);
            $acsDefendTotalStart->addCollection($defendingUnits);
        }

        // Calculate the moon's units (ships + defenses) at start
        $moonUnitsStart = clone $battleResult->defenderUnitsStart;
        $moonUnitsStart->subtractCollection($acsDefendTotalStart);

        // Calculate how many of the moon's units survived
        // First, we need to figure out which survivors belong to the moon vs ACS Defend missions
        // We'll use a proportional distribution based on each unit type
        foreach ($battleResult->defendingMissions as $defendingMission) {
            // Get the original units for this mission
            $missionUnitsStart = $this->fleetMissionService->getFleetUnits($defendingMission);

            // Calculate surviving units for this mission based on overall survival rates
            $missionUnitsSurvived = new UnitCollection();

            foreach ($missionUnitsStart->units as $unit) {
                $unitMachineName = $unit->unitObject->machine_name;
                $originalAmount = $unit->amount;

                // Get the total amount of this unit type at battle start (moon + all ACS Defend)
                $totalStartAmount = $battleResult->defenderUnitsStart->getAmountByMachineName($unitMachineName);

                if ($totalStartAmount > 0) {
                    // Get the total survivors of this unit type
                    $totalSurvived = $battleResult->defenderUnitsResult->getAmountByMachineName($unitMachineName);

                    // Calculate survival rate
                    $survivalRate = $totalSurvived / $totalStartAmount;

                    // Apply survival rate to this mission's original units (round down)
                    $survivedAmount = (int)floor($originalAmount * $survivalRate);

                    if ($survivedAmount > 0) {
                        $missionUnitsSurvived->addUnit($unit->unitObject, $survivedAmount);
                    }
                }
            }

            \Log::info('ACS Defend mission ' . $defendingMission->id . ' participated in moon destruction battle', [
                'original_units' => $missionUnitsStart->toArray(),
                'surviving_units' => $missionUnitsSurvived->toArray(),
                'mission_type' => $defendingMission->mission_type,
            ]);

            // Update the defending mission's units to reflect battle losses
            // First, set all original unit types to 0 (destroyed)
            foreach ($missionUnitsStart->units as $unit) {
                $defendingMission->{$unit->unitObject->machine_name} = 0;
            }
            // Then set the surviving units
            foreach ($missionUnitsSurvived->units as $unit) {
                $defendingMission->{$unit->unitObject->machine_name} = $unit->amount;
            }
            $defendingMission->save();

            \Log::debug('Updated ACS Defend mission units after moon destruction battle', [
                'mission_id' => $defendingMission->id,
                'surviving_units' => $missionUnitsSurvived->toArray(),
                'will_return_at' => $defendingMission->time_arrival + ($defendingMission->time_holding ?? 0),
            ]);
        }

        // Check if attacker fleet was destroyed in first round
        $attackerDestroyedFirstRound = false;
        if (count($battleResult->rounds) > 0) {
            $firstRound = $battleResult->rounds[0];
            if ($firstRound->attackerShips->getAmount() === 0) {
                $attackerDestroyedFirstRound = true;
            }
        }

        // Create battle report
        $reportId = $this->createBattleReport($attackerPlayer, $defenderMoon, $battleResult, $repairedDefenses);

        // Send battle report messages
        if ($attackerDestroyedFirstRound) {
            // Send simplified "fleet lost contact" message to attacker (no fleet or tech info)
            $coordinates = '[coordinates]' . $defenderMoon->getPlanetCoordinates()->asString() . '[/coordinates]';
            $this->messageService->sendSystemMessageToPlayer($attackerPlayer, \OGame\GameMessages\FleetLostContact::class, [
                'coordinates' => $coordinates,
            ]);
        } else {
            // Normal: send full battle report to attacker
            $this->messageService->sendBattleReportMessageToPlayer($attackerPlayer, $reportId);
        }

        // Always send full battle report to defender (moon owner)
        $this->messageService->sendBattleReportMessageToPlayer($defenderMoon->getPlayer(), $reportId);

        // Send battle report to all ACS Defend fleet owners (only once per player)
        $reportedDefenders = [$defenderMoon->getPlayer()->getId()]; // Moon owner already reported
        foreach ($battleResult->defendingMissions as $defendingMission) {
            $defendingPlayer = resolve(\OGame\Services\PlayerService::class, ['player_id' => $defendingMission->user_id]);

            // Only send once per unique defending player
            if (!in_array($defendingPlayer->getId(), $reportedDefenders)) {
                $this->messageService->sendBattleReportMessageToPlayer($defendingPlayer, $reportId);
                $reportedDefenders[] = $defendingPlayer->getId();
            }
        }

        // Determine outcome after battle
        $moonDestroyed = false;
        $fleetDestroyed = false;

        // If attacker won the battle, attempt moon destruction
        if ($battleResult->attackerUnitsResult->getAmount() > 0) {
            // Roll for moon destruction (INDEPENDENT roll #1)
            $moonDestructionRoll = random_int(1, 10000) / 100; // Random percentage with 2 decimal precision
            $moonDestroyed = ($moonDestructionRoll <= $moonDestructionChance);

            // Roll for Death Star destruction (INDEPENDENT roll #2)
            $deathStarDestructionRoll = random_int(1, 10000) / 100;
            $fleetDestroyed = ($deathStarDestructionRoll <= $deathStarDestructionChance);

            // Handle moon destruction if successful
            if ($moonDestroyed) {
                // Redirect all fleets heading to this moon to the planet
                $this->redirectFleetsToMoon($defenderMoon);

                // Delete the moon
                $defenderMoon->abandonPlanet();

                // Send moon destroyed message to defender
                $this->sendMoonDestroyedMessage($defenderMoon->getPlayer(), $defenderMoon);
            }

            // Send appropriate message to attacker based on the 4 possible outcomes
            if ($moonDestroyed && !$fleetDestroyed) {
                // Outcome 1: Moon destroyed, Fleet survives
                $this->sendMoonDestructionSuccessMessage($mission, $attackerPlayer, $origin_planet, $defenderMoon, $moonDestructionChance, $deathStarDestructionChance);
            } elseif ($moonDestroyed && $fleetDestroyed) {
                // Outcome 2: Moon destroyed, Fleet destroyed
                $this->sendMoonDestructionSuccessButFleetDestroyedMessage($mission, $attackerPlayer, $origin_planet, $defenderMoon, $moonDestructionChance, $deathStarDestructionChance);
            } elseif (!$moonDestroyed && $fleetDestroyed) {
                // Outcome 3: Moon survives, Fleet destroyed
                $this->sendFleetDestroyedMessage($mission, $attackerPlayer, $origin_planet, $defenderMoon, $moonDestructionChance, $deathStarDestructionChance);
            } else {
                // Outcome 4: Moon survives, Fleet survives
                $this->sendMoonDestructionFailedMessage($mission, $attackerPlayer, $origin_planet, $defenderMoon, $moonDestructionChance, $deathStarDestructionChance);
            }

            // Send message to defender
            $this->sendMoonDestructionAttemptMessage($defenderMoon->getPlayer(), $defenderMoon, $fleetDestroyed, $moonDestroyed);
        } else {
            // Attacker lost the battle, fleet is already destroyed
            $fleetDestroyed = true;

            // Send battle loss message to defender
            $this->sendMoonDestructionAttemptMessage($defenderMoon->getPlayer(), $defenderMoon, false, false);
        }

        // Mark the arrival mission as processed
        $mission->processed = 1;
        $mission->save();

        // Create and start the return mission (if attacker has remaining units and fleet was not destroyed).
        if (!$fleetDestroyed) {
            $this->startReturn($mission, $this->fleetMissionService->getResources($mission), $battleResult->attackerUnitsResult);
        } else {
            // Fleet destroyed - no return mission
            $this->startReturn($mission, $this->fleetMissionService->getResources($mission), new UnitCollection());
        }
    }

    /**
     * @inheritdoc
     */
    protected function processReturn(FleetMission $mission): void
    {
        // Load the target planet
        $target_planet = $this->planetServiceFactory->make($mission->planet_id_to, true);

        // Moon destruction return trip: add back the units to the source planet. Then we're done.
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

    /**
     * Calculate moon destruction chance percentage.
     * Formula: (100 - sqrt(moon_size_km)) * sqrt(death_stars)
     *
     * @param int $moonSizeKm Moon size in kilometers
     * @param int $deathStarCount Number of Death Stars
     * @return float Percentage chance (0-100)
     */
    private function calculateMoonDestructionChance(int $moonSizeKm, int $deathStarCount): float
    {
        $chance = (100 - sqrt($moonSizeKm)) * sqrt($deathStarCount);
        return min(100, max(0, $chance)); // Clamp between 0 and 100
    }

    /**
     * Calculate Death Star destruction chance percentage.
     * Formula: sqrt(moon_size_km) / 2
     *
     * @param int $moonSizeKm Moon size in kilometers
     * @return float Percentage chance (0-100)
     */
    private function calculateDeathStarDestructionChance(int $moonSizeKm): float
    {
        return sqrt($moonSizeKm) / 2;
    }

    /**
     * Redirect all fleets heading to the destroyed moon to the planet.
     *
     * @param PlanetService $moon The moon that was destroyed
     * @return void
     */
    private function redirectFleetsToMoon(PlanetService $moon): void
    {
        // Find all fleet missions heading to this moon
        $coordinate = $moon->getPlanetCoordinates();

        $missions = FleetMission::where('galaxy_to', $coordinate->galaxy)
            ->where('system_to', $coordinate->system)
            ->where('position_to', $coordinate->position)
            ->where('type_to', PlanetType::Moon->value)
            ->where('processed', 0)
            ->get();

        // Redirect each mission to the planet
        foreach ($missions as $mission) {
            $mission->type_to = PlanetType::Planet->value;

            // Update planet_id_to to the parent planet
            $planet = $this->planetServiceFactory->makeForCoordinate($coordinate, true, PlanetType::Planet);
            if ($planet !== null) {
                $mission->planet_id_to = $planet->getPlanetId();
            }

            $mission->save();
        }
    }

    /**
     * Creates a battle report for the given battle result.
     *
     * @param PlayerService $attackPlayer The player who initiated the attack.
     * @param PlanetService $defenderMoon The moon that was attacked.
     * @param BattleResult $battleResult The result of the battle.
     * @param UnitCollection $repairedDefenses The defensive structures that were repaired after the battle.
     * @return int
     */
    private function createBattleReport(PlayerService $attackPlayer, PlanetService $defenderMoon, BattleResult $battleResult, UnitCollection $repairedDefenses): int
    {
        // Create new battle report record.
        $report = new BattleReport();
        $report->planet_galaxy = $defenderMoon->getPlanetCoordinates()->galaxy;
        $report->planet_system = $defenderMoon->getPlanetCoordinates()->system;
        $report->planet_position = $defenderMoon->getPlanetCoordinates()->position;
        $report->planet_type = $defenderMoon->getPlanetType()->value;

        $report->planet_user_id = $defenderMoon->getPlayer()->getId();

        $report->general = [
            'moon_existed' => $battleResult->moonExisted,
            'moon_chance' => $battleResult->moonChance,
            'moon_created' => $battleResult->moonCreated,
            'moon_destruction_mission' => true, // Flag to indicate this is a moon destruction mission
            'defender_moon_name' => $defenderMoon->getPlanetName(), // Store moon name before it's potentially deleted
        ];

        $report->attacker = [
            'player_id' => $attackPlayer->getId(),
            'resource_loss' => $battleResult->attackerResourceLoss->sum(),
            'units' => $battleResult->attackerUnitsStart->toArray(),
            'weapon_technology' => $battleResult->attackerWeaponLevel,
            'shielding_technology' => $battleResult->attackerShieldLevel,
            'armor_technology' => $battleResult->attackerArmorLevel,
        ];

        $report->defender = [
            'player_id' => $defenderMoon->getPlayer()->getId(),
            'resource_loss' => $battleResult->defenderResourceLoss->sum(),
            'units' => $battleResult->defenderUnitsStart->toArray(),
            'weapon_technology' => $battleResult->defenderWeaponLevel,
            'shielding_technology' => $battleResult->defenderShieldLevel,
            'armor_technology' => $battleResult->defenderArmorLevel,
        ];

        $report->loot = [
            'percentage' => 0, // No loot in moon destruction
            'metal' => 0,
            'crystal' => 0,
            'deuterium' => 0,
        ];

        $report->debris = [
            'metal' => 0, // No debris in moon destruction
            'crystal' => 0,
            'deuterium' => 0,
        ];

        $repairedDefensesArray = $repairedDefenses->toArray();

        $report->repaired_defenses = $repairedDefensesArray;

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

    /**
     * Calculate which defensive structures are repaired after battle.
     * In OGame, each destroyed defensive structure has a 70% chance to be rebuilt.
     *
     * @param UnitCollection $defenderUnitsLost The units lost by the defender during battle.
     * @return UnitCollection Collection of repaired defensive structures.
     * @throws \Exception
     */
    private function calculateRepairedDefenses(UnitCollection $defenderUnitsLost): UnitCollection
    {
        $repairedDefenses = new UnitCollection();

        // Get all defense objects to identify which lost units are defensive structures
        $defenseObjects = ObjectService::getDefenseObjects();
        $defenseObjectMachineNames = array_column($defenseObjects, 'machine_name');

        // Process each lost unit
        foreach ($defenderUnitsLost->units as $unit) {
            // Check if this unit is a defensive structure (ships are not repaired)
            if (in_array($unit->unitObject->machine_name, $defenseObjectMachineNames)) {
                // Roll 70% chance for each individual defensive structure
                $repairedCount = 0;
                for ($i = 0; $i < $unit->amount; $i++) {
                    // Generate random number 1-100, if <= 70 then repair this unit (70% chance)
                    if (random_int(1, 100) <= 70) {
                        $repairedCount++;
                    }
                }

                // Add repaired defenses to the collection
                if ($repairedCount > 0) {
                    $repairedDefenses->addUnit($unit->unitObject, $repairedCount);
                }
            }
        }

        return $repairedDefenses;
    }

    /**
     * Send message when moon does not exist.
     */
    private function sendMoonNotFoundMessage(FleetMission $mission, PlayerService $player): void
    {
        $this->messageService->sendSystemMessageToPlayer($player, \OGame\GameMessages\MoonDestructionMoonNotFound::class, [
            'from' => '[planet]' . $mission->planet_id_from . '[/planet]',
            'to' => '[coordinates]' . $mission->galaxy_to . ':' . $mission->system_to . ':' . $mission->position_to . '[/coordinates]',
        ]);
    }

    /**
     * Send moon destruction success message to attacker.
     */
    private function sendMoonDestructionSuccessMessage(FleetMission $mission, PlayerService $player, PlanetService $originPlanet, PlanetService $targetMoon, float $moonChance, float $deathStarChance): void
    {
        $this->messageService->sendSystemMessageToPlayer($player, \OGame\GameMessages\MoonDestructionSuccess::class, [
            'from' => '[planet]' . $originPlanet->getPlanetId() . '[/planet]',
            'to' => '[coordinates]' . $targetMoon->getPlanetCoordinates()->asString() . '[/coordinates]',
            'moon_chance' => number_format($moonChance, 2),
            'deathstar_chance' => number_format($deathStarChance, 2),
        ]);
    }

    /**
     * Send moon destruction failed message to attacker (fleet survives).
     */
    private function sendMoonDestructionFailedMessage(FleetMission $mission, PlayerService $player, PlanetService $originPlanet, PlanetService $targetMoon, float $moonChance, float $deathStarChance): void
    {
        $this->messageService->sendSystemMessageToPlayer($player, \OGame\GameMessages\MoonDestructionFailed::class, [
            'from' => '[planet]' . $originPlanet->getPlanetId() . '[/planet]',
            'to' => '[coordinates]' . $targetMoon->getPlanetCoordinates()->asString() . '[/coordinates]',
            'moon_chance' => number_format($moonChance, 2),
            'deathstar_chance' => number_format($deathStarChance, 2),
        ]);
    }

    /**
     * Send moon destruction success but fleet destroyed message to attacker.
     */
    private function sendMoonDestructionSuccessButFleetDestroyedMessage(FleetMission $mission, PlayerService $player, PlanetService $originPlanet, PlanetService $targetMoon, float $moonChance, float $deathStarChance): void
    {
        $this->messageService->sendSystemMessageToPlayer($player, \OGame\GameMessages\MoonDestructionSuccessButFleetDestroyed::class, [
            'from' => '[planet]' . $originPlanet->getPlanetId() . '[/planet]',
            'to' => '[coordinates]' . $targetMoon->getPlanetCoordinates()->asString() . '[/coordinates]',
            'moon_chance' => number_format($moonChance, 2),
            'deathstar_chance' => number_format($deathStarChance, 2),
        ]);
    }

    /**
     * Send fleet destroyed message to attacker (Death Stars explode, moon survives).
     */
    private function sendFleetDestroyedMessage(FleetMission $mission, PlayerService $player, PlanetService $originPlanet, PlanetService $targetMoon, float $moonChance, float $deathStarChance): void
    {
        $this->messageService->sendSystemMessageToPlayer($player, \OGame\GameMessages\MoonDestructionFleetDestroyed::class, [
            'from' => '[planet]' . $originPlanet->getPlanetId() . '[/planet]',
            'to' => '[coordinates]' . $targetMoon->getPlanetCoordinates()->asString() . '[/coordinates]',
            'moon_chance' => number_format($moonChance, 2),
            'deathstar_chance' => number_format($deathStarChance, 2),
        ]);
    }

    /**
     * Send message to defender about moon destruction attempt.
     */
    private function sendMoonDestructionAttemptMessage(PlayerService $player, PlanetService $moon, bool $attackerFleetDestroyed, bool $moonDestroyed): void
    {
        // Determine result text based on outcome
        if ($moonDestroyed && $attackerFleetDestroyed) {
            $result = 'Your moon was destroyed, but the attacking fleet was also destroyed in the process.';
        } elseif ($moonDestroyed && !$attackerFleetDestroyed) {
            $result = 'Your moon was destroyed. The attacking fleet is returning.';
        } elseif (!$moonDestroyed && $attackerFleetDestroyed) {
            $result = 'Your moon survived! The attacking fleet was destroyed by graviton feedback.';
        } else {
            $result = 'Your moon survived and the attacking fleet is returning.';
        }

        $this->messageService->sendSystemMessageToPlayer($player, \OGame\GameMessages\MoonDestructionAttempt::class, [
            'coordinates' => '[coordinates]' . $moon->getPlanetCoordinates()->asString() . '[/coordinates]',
            'result' => $result,
        ]);
    }

    /**
     * Send message to defender that their moon was destroyed.
     */
    private function sendMoonDestroyedMessage(PlayerService $player, PlanetService $moon): void
    {
        $this->messageService->sendSystemMessageToPlayer($player, \OGame\GameMessages\MoonDestroyed::class, [
            'coordinates' => '[coordinates]' . $moon->getPlanetCoordinates()->asString() . '[/coordinates]',
        ]);
    }
}
