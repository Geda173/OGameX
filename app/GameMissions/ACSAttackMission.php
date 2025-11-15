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
use OGame\Models\Resources;
use OGame\Services\ACSService;
use OGame\Services\DebrisFieldService;
use OGame\Services\ObjectService;
use OGame\Services\PlanetService;
use OGame\Services\PlayerService;
use Throwable;

class ACSAttackMission extends GameMission
{
    protected static string $name = 'ACS Attack';
    protected static int $typeId = 2;
    protected static bool $hasReturnMission = true;

    /**
     * @inheritdoc
     */
    public function isMissionPossible(PlanetService $planet, Coordinate $targetCoordinate, PlanetType $targetType, UnitCollection $units): MissionPossibleStatus
    {
        // ACS Attack mission is only possible for planets and moons.
        if (!in_array($targetType, [PlanetType::Planet, PlanetType::Moon])) {
            return new MissionPossibleStatus(false);
        }

        // If target planet does not exist, the mission is not possible.
        $targetPlanet = $this->planetServiceFactory->makeForCoordinate($targetCoordinate, true, $targetType);
        if ($targetPlanet === null) {
            return new MissionPossibleStatus(false);
        }

        // If planet belongs to current player, the mission is not possible.
        if ($planet->getPlayer()->equals($targetPlanet->getPlayer())) {
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
     * @throws Throwable
     */
    protected function processArrival(FleetMission $mission): void
    {
        // For ACS missions, we need to find the ACS group this fleet belongs to
        $acsFleetMember = \OGame\Models\AcsFleetMember::where('fleet_mission_id', $mission->id)->first();

        if (!$acsFleetMember) {
            // This shouldn't happen, but fall back to regular attack if no ACS group found
            $this->processSingleFleetAttack($mission);
            return;
        }

        $acsGroup = ACSService::findGroup($acsFleetMember->acs_group_id);

        if (!$acsGroup) {
            // Group not found, fall back to single fleet attack
            $this->processSingleFleetAttack($mission);
            return;
        }

        // Check if group has already been processed
        if ($acsGroup->status === 'completed' || $acsGroup->status === 'cancelled') {
            // Group already processed, mark this mission as processed too
            $mission->processed = 1;
            $mission->save();
            return;
        }

        // Check if all fleets in the group have arrived
        $allArrived = ACSService::allFleetsArrived($acsGroup);
        \Log::debug('ACS Group arrival check', [
            'acs_group_id' => $acsGroup->id,
            'mission_id' => $mission->id,
            'all_fleets_arrived' => $allArrived,
            'current_time' => time(),
            'group_arrival_time' => $acsGroup->arrival_time,
        ]);

        if (!$allArrived) {
            // Not all fleets have arrived yet, don't process yet
            // The mission will be processed when the update cycle runs again
            \Log::debug('Not all fleets arrived yet, waiting...', [
                'acs_group_id' => $acsGroup->id,
                'fleet_count' => ACSService::getGroupFleets($acsGroup)->count(),
            ]);
            return;
        }

        \Log::debug('All fleets arrived! Attempting to process combined attack', [
            'acs_group_id' => $acsGroup->id,
            'status' => $acsGroup->status,
        ]);

        // Use database transaction with row locking to prevent race conditions
        // If multiple fleets arrive simultaneously, only one will process the attack
        \DB::transaction(function () use ($acsGroup, $mission) {
            // Lock the ACS group row for update (prevents concurrent processing)
            $lockedGroup = \OGame\Models\AcsGroup::where('id', $acsGroup->id)
                ->lockForUpdate()
                ->first();

            // Double-check status after acquiring lock
            if ($lockedGroup->status === 'pending' || $lockedGroup->status === 'active') {
                \Log::info('Processing ACS group attack (lock acquired)', [
                    'acs_group_id' => $lockedGroup->id,
                    'mission_id' => $mission->id,
                ]);

                $this->processACSGroupAttack($lockedGroup, $mission);
            } else {
                \Log::debug('ACS group already processed by another fleet', [
                    'acs_group_id' => $lockedGroup->id,
                    'status' => $lockedGroup->status,
                    'mission_id' => $mission->id,
                ]);

                // Mark this mission as processed since the group was already handled
                $mission->processed = 1;
                $mission->save();
            }
        });
    }

    /**
     * Process a combined ACS attack with all fleets in the group.
     *
     * @param \OGame\Models\AcsGroup $acsGroup
     * @param FleetMission $triggeringMission The mission that triggered this processing
     * @return void
     * @throws Throwable
     */
    private function processACSGroupAttack(\OGame\Models\AcsGroup $acsGroup, FleetMission $triggeringMission): void
    {
        $defenderPlanet = $this->planetServiceFactory->make($triggeringMission->planet_id_to, true);

        // Trigger defender planet update to make sure the battle uses up-to-date info.
        $defenderPlanet->update();

        // Get all fleet missions in this ACS group
        $fleetMembers = ACSService::getGroupFleets($acsGroup);

        // Combine all attacker units
        $combinedAttackerUnits = new UnitCollection();
        $attackerFleets = [];

        foreach ($fleetMembers as $member) {
            // Use only_active=false to allow fetching missions that may have been processed
            // (e.g., if one fleet in the group failed validation and was marked as processed)
            $fleetMission = $this->fleetMissionService->getFleetMissionById($member->fleet_mission_id, false);

            if (!$fleetMission) {
                \Log::warning('ACS group fleet mission not found', [
                    'acs_group_id' => $acsGroup->id,
                    'fleet_mission_id' => $member->fleet_mission_id,
                ]);
                continue;
            }

            // Skip missions that have already been processed
            if ($fleetMission->processed) {
                \Log::debug('Skipping already processed fleet in ACS group', [
                    'mission_id' => $fleetMission->id,
                    'acs_group_id' => $acsGroup->id,
                ]);
                continue;
            }

            $fleetUnits = $this->fleetMissionService->getFleetUnits($fleetMission);

            // CRITICAL FIX: Clone the units before adding to combined force
            // addCollection() adds Unit objects by reference, so without cloning,
            // all fleet units would end up pointing to the same mutated objects!
            $fleetUnitsForStorage = clone $fleetUnits;

            // Add this fleet's units to the combined force
            $combinedAttackerUnits->addCollection($fleetUnits);

            // Store fleet info for later processing (use the cloned version)
            $originPlanet = $this->planetServiceFactory->make($fleetMission->planet_id_from, true);
            $fleetPlayer = $originPlanet->getPlayer();
            $attackerFleets[] = [
                'mission' => $fleetMission,
                'player' => $fleetPlayer,
                'units' => $fleetUnitsForStorage,  // Use cloned version
                'cargo_capacity' => $fleetUnitsForStorage->getTotalCargoCapacity($fleetPlayer),
            ];
        }

        // Check if we have any valid fleets to attack with
        if (empty($attackerFleets)) {
            \Log::error('ACS group has no valid fleets to attack with', [
                'acs_group_id' => $acsGroup->id,
            ]);
            // Mark the group as completed since there are no valid fleets
            ACSService::completeGroup($acsGroup);
            return;
        }

        // Use the first attacker's player for battle engine (all attackers fight together)
        $primaryAttacker = $attackerFleets[0]['player'];

        // Execute the battle logic using configured battle engine
        switch ($this->settings->battleEngine()) {
            case 'php':
                $battleEngine = new PhpBattleEngine($combinedAttackerUnits, $primaryAttacker, $defenderPlanet, $this->settings);
                break;
            case 'rust':
            default:
                $battleEngine = new RustBattleEngine($combinedAttackerUnits, $primaryAttacker, $defenderPlanet, $this->settings);
                break;
        }

        $battleResult = $battleEngine->simulateBattle();

        // Deduct loot from the target planet.
        $defenderPlanet->deductResources($battleResult->loot);

        // Calculate total defender losses
        $defenderUnitsLost = clone $battleResult->defenderUnitsStart;
        $defenderUnitsLost->subtractCollection($battleResult->defenderUnitsResult);

        // Calculate ACS Defend units that participated (these are NOT on the planet)
        $acsDefendUnitsStart = new UnitCollection();
        foreach ($battleResult->defendingMissions as $defendingMission) {
            $defendingUnits = $this->fleetMissionService->getFleetUnits($defendingMission);
            $acsDefendUnitsStart->addCollection($defendingUnits);
        }

        // Only remove planet's own losses (exclude ACS Defend units that were in fleet missions)
        // We need to calculate which losses belong to the planet vs ACS Defend fleets
        $planetUnitsLost = new UnitCollection();
        foreach ($defenderUnitsLost->units as $unit) {
            $unitMachineName = $unit->unitObject->machine_name;
            $totalLost = $unit->amount;

            // Get how many of this unit type were from ACS Defend missions
            $acsDefendAmount = $acsDefendUnitsStart->getAmountByMachineName($unitMachineName);

            // The planet can only lose units it actually had, not ACS Defend units
            // So we subtract the ACS Defend starting amount from the losses
            $planetActualLoss = max(0, $totalLost - $acsDefendAmount);

            // Only add to planet losses if planet actually lost units
            if ($planetActualLoss > 0) {
                $planetUnitsLost->addUnit($unit->unitObject, $planetActualLoss);
            }
        }

        $defenderPlanet->removeUnits($planetUnitsLost, false);

        // Calculate repaired defenses (70% chance for each destroyed defense structure)
        $repairedDefenses = $this->calculateRepairedDefenses($defenderUnitsLost);

        // Add repaired defenses back to the planet
        if ($repairedDefenses->getAmount() > 0) {
            $defenderPlanet->addUnits($repairedDefenses, false);
        }

        // Save defenders planet
        $defenderPlanet->save();

        // Create or append debris field.
        $debrisFieldService = resolve(DebrisFieldService::class);
        $debrisFieldService->loadOrCreateForCoordinates($defenderPlanet->getPlanetCoordinates());
        $debrisFieldService->appendResources($battleResult->debris);
        $debrisFieldService->save();

        // Create a moon for defender if result of battle indicates so and defender planet does not already have a moon.
        if (!$defenderPlanet->hasMoon() && $battleResult->moonCreated) {
            $this->planetServiceFactory->createMoonForPlanet($defenderPlanet);
        }

        // Handle ACS Defend missions that participated in the battle
        // Calculate survival rate for each unit type and distribute survivors back to defending missions
        $acsDefendTotalStart = new UnitCollection();
        foreach ($battleResult->defendingMissions as $defendingMission) {
            $defendingUnits = $this->fleetMissionService->getFleetUnits($defendingMission);
            $acsDefendTotalStart->addCollection($defendingUnits);
        }

        // Calculate the planet's units (ships + defenses) at start
        $planetUnitsStart = clone $battleResult->defenderUnitsStart;
        $planetUnitsStart->subtractCollection($acsDefendTotalStart);

        // Calculate how many of the planet's units survived
        // First, we need to figure out which survivors belong to the planet vs ACS Defend missions
        // We'll use a proportional distribution based on each unit type
        foreach ($battleResult->defendingMissions as $defendingMission) {
            // Get the original units for this mission
            $missionUnitsStart = $this->fleetMissionService->getFleetUnits($defendingMission);

            // Calculate surviving units for this mission based on overall survival rates
            $missionUnitsSurvived = new UnitCollection();

            foreach ($missionUnitsStart->units as $unit) {
                $unitMachineName = $unit->unitObject->machine_name;
                $originalAmount = $unit->amount;

                // Get totals for this unit type
                $totalStartForUnit = $acsDefendTotalStart->getAmountByMachineName($unitMachineName);

                if ($totalStartForUnit > 0) {
                    // Get surviving amount from battle result for this unit type (only ships, not defenses)
                    $totalSurvivedForUnit = $battleResult->defenderUnitsResult->getAmountByMachineName($unitMachineName);

                    // Subtract planet's original amount to get ACS defend survivors
                    $planetOriginalForUnit = $planetUnitsStart->getAmountByMachineName($unitMachineName);
                    $acsDefendSurvivedForUnit = max(0, $totalSurvivedForUnit - $planetOriginalForUnit);

                    // Calculate this mission's share of survivors based on proportion
                    $missionProportion = $originalAmount / $totalStartForUnit;
                    $missionSurvived = (int)floor($acsDefendSurvivedForUnit * $missionProportion);

                    if ($missionSurvived > 0) {
                        $missionUnitsSurvived->addUnit($unit->unitObject, $missionSurvived);
                    }
                }
            }

            \Log::info('ACS Defend mission ' . $defendingMission->id . ' participated in battle against ACS group ' . $acsGroup->id, [
                'original_units' => $missionUnitsStart->toArray(),
                'surviving_units' => $missionUnitsSurvived->toArray(),
            ]);

            // Update the defending mission's units to reflect battle losses
            // The mission will continue holding and return when its hold duration expires
            foreach ($missionUnitsSurvived->units as $unit) {
                $defendingMission->{$unit->unitObject->machine_name} = $unit->amount;
            }
            $defendingMission->save();

            \Log::debug('Updated ACS Defend mission units after battle', [
                'mission_id' => $defendingMission->id,
                'surviving_units' => $missionUnitsSurvived->toArray(),
                'will_return_at' => $defendingMission->time_arrival + ($defendingMission->time_holding ?? 0),
            ]);
        }

        // Distribute loot and losses among all attackers
        $this->distributeLootAndLosses($attackerFleets, $battleResult, $defenderPlanet, $repairedDefenses);

        // Mark the ACS group as completed (also cleans up pending invitations)
        ACSService::completeGroup($acsGroup);
    }

    /**
     * Distribute loot and losses among all participating attackers.
     *
     * @param array $attackerFleets Array of attacker fleet information
     * @param BattleResult $battleResult The battle result
     * @param PlanetService $defenderPlanet The defender's planet
     * @param UnitCollection $repairedDefenses Repaired defense units
     * @return void
     */
    private function distributeLootAndLosses(array $attackerFleets, BattleResult $battleResult, PlanetService $defenderPlanet, UnitCollection $repairedDefenses): void
    {
        // First pass: Calculate surviving units and cargo capacity for each fleet
        $fleetsWithSurvivors = [];
        $totalSurvivingCargoCapacity = 0;

        foreach ($attackerFleets as $fleet) {
            $initialUnits = $fleet['units'];

            // DEBUG: Log what units this fleet sent
            \Log::info('ACS distributeLootAndLosses - Fleet units', [
                'player_id' => $fleet['player']->getId(),
                'mission_id' => $fleet['mission']->id,
                'units_sent' => $initialUnits->toArray(),
            ]);

            // FIXED: Use ship-type-specific survival rates from battle result
            // instead of applying a uniform loss percentage to all ship types
            $survivingUnits = $this->calculateSurvivingUnitsCorrect($initialUnits, $battleResult);

            // DEBUG: Log what survivors were calculated
            \Log::info('ACS distributeLootAndLosses - Calculated survivors', [
                'player_id' => $fleet['player']->getId(),
                'mission_id' => $fleet['mission']->id,
                'survivors' => $survivingUnits->toArray(),
            ]);

            // Calculate surviving cargo capacity
            $survivingCargoCapacity = $survivingUnits->getTotalCargoCapacity($fleet['player']);
            $totalSurvivingCargoCapacity += $survivingCargoCapacity;

            $fleetsWithSurvivors[] = [
                'mission' => $fleet['mission'],
                'player' => $fleet['player'],
                'surviving_units' => $survivingUnits,
                'surviving_cargo_capacity' => $survivingCargoCapacity,
            ];
        }

        // Get total loot from battle result
        $totalLoot = $battleResult->loot;

        // Distribute loot and losses among all participating attackers.
        // Create ONE battle report for the ACS attack (sent to all players)
        $reportId = $this->createBattleReport($attackerFleets[0]['player'], $defenderPlanet, $battleResult, $repairedDefenses, true, $attackerFleets);

        // Track unique players to avoid sending duplicate reports to same player
        $reportedPlayers = [];

        // Second pass: Distribute loot based on surviving cargo capacity
        foreach ($fleetsWithSurvivors as $fleetData) {
            $mission = $fleetData['mission'];
            $player = $fleetData['player'];
            $survivingUnits = $fleetData['surviving_units'];
            $survivingCargoCapacity = $fleetData['surviving_cargo_capacity'];

            // Send battle report to this attacker (only once per player)
            if (!in_array($player->getId(), $reportedPlayers)) {
                $this->messageService->sendBattleReportMessageToPlayer($player, $reportId);
                $reportedPlayers[] = $player->getId();
            }

            // Calculate this fleet's share of loot based on SURVIVING cargo capacity
            $cargoShare = $totalSurvivingCargoCapacity > 0 ? ($survivingCargoCapacity / $totalSurvivingCargoCapacity) : 0;
            $fleetLoot = new Resources(
                (int)($totalLoot->metal->get() * $cargoShare),
                (int)($totalLoot->crystal->get() * $cargoShare),
                (int)($totalLoot->deuterium->get() * $cargoShare),
                0
            );

            // Mark mission as processed
            $mission->processed = 1;
            $mission->save();

            // Create return mission with loot and surviving units
            $this->startReturn($mission, $fleetLoot, $survivingUnits);
        }

        // Send battle report to defender (same report, not a duplicate)
        $this->messageService->sendBattleReportMessageToPlayer($defenderPlanet->getPlayer(), $reportId);

        // Send battle report to all ACS Defend fleet owners (only once per player)
        $reportedDefenders = [$defenderPlanet->getPlayer()->getId()]; // Planet owner already reported
        foreach ($battleResult->defendingMissions as $defendingMission) {
            $defendingPlayer = resolve(\OGame\Services\PlayerService::class, ['player_id' => $defendingMission->user_id]);

            // Only send once per unique defending player
            if (!in_array($defendingPlayer->getId(), $reportedDefenders)) {
                $this->messageService->sendBattleReportMessageToPlayer($defendingPlayer, $reportId);
                $reportedDefenders[] = $defendingPlayer->getId();
            }
        }
    }

    /**
     * Calculate the percentage of losses for a specific fleet.
     *
     * @deprecated This method is BUGGY - it calculates a uniform loss percentage for all ship types.
     *             The correct approach is to use ship-type-specific survival rates.
     *             This method is no longer used. See calculateSurvivingUnitsCorrect() for the fix.
     *
     * @param UnitCollection $fleetUnits
     * @param BattleResult $battleResult
     * @return float Loss percentage (0-1)
     */
    private function calculateFleetLossPercentage(UnitCollection $fleetUnits, BattleResult $battleResult): float
    {
        // Calculate what percentage of total attacking force this fleet represented
        $fleetValue = 0;
        $totalStartValue = 0;

        foreach ($fleetUnits->units as $unit) {
            $unitValue = $unit->amount * $unit->unitObject->price->resources->metal->get();
            $fleetValue += $unitValue;
        }

        foreach ($battleResult->attackerUnitsStart->units as $unit) {
            $unitValue = $unit->amount * $unit->unitObject->price->resources->metal->get();
            $totalStartValue += $unitValue;
        }

        $fleetPercentage = $totalStartValue > 0 ? ($fleetValue / $totalStartValue) : 0;

        // Calculate total attacker losses
        $totalLosses = clone $battleResult->attackerUnitsStart;
        $totalLosses->subtractCollection($battleResult->attackerUnitsResult);

        $lossValue = 0;
        foreach ($totalLosses->units as $unit) {
            $unitValue = $unit->amount * $unit->unitObject->price->resources->metal->get();
            $lossValue += $unitValue;
        }

        // Return the percentage of losses relative to fleet size
        return $fleetValue > 0 ? ($lossValue * $fleetPercentage / $fleetValue) : 0;
    }

    /**
     * Calculate surviving units for a fleet based on loss percentage.
     *
     * @deprecated This method is BUGGY - it applies a uniform loss percentage to all ship types.
     *             Use calculateSurvivingUnitsCorrect() instead.
     *
     * @param UnitCollection $originalUnits
     * @param float $lossPercentage
     * @return UnitCollection
     */
    private function calculateSurvivingUnits(UnitCollection $originalUnits, float $lossPercentage): UnitCollection
    {
        $survivingUnits = new UnitCollection();

        foreach ($originalUnits->units as $unit) {
            $surviving = (int)($unit->amount * (1 - $lossPercentage));
            if ($surviving > 0) {
                $survivingUnits->addUnit($unit->unitObject, $surviving);
            }
        }

        return $survivingUnits;
    }

    /**
     * CORRECTED: Calculate surviving units for a fleet based on ship-type-specific survival rates.
     *
     * This method correctly distributes battle survivors to each fleet proportionally based on:
     * 1. The actual survival rate of each ship type from the battle
     * 2. What this specific fleet contributed for each ship type
     *
     * Bug fix for: Ship duplication where fleets received more ships than should have survived.
     * Original bug: calculateFleetLossPercentage() + calculateSurvivingUnits() applied a uniform
     * loss percentage to all ship types, ignoring ship-type-specific survival rates.
     *
     * @param UnitCollection $fleetUnits The units this specific fleet sent to the battle
     * @param BattleResult $battleResult The battle result containing total survivors
     * @return UnitCollection The units this fleet should receive back
     */
    private function calculateSurvivingUnitsCorrect(UnitCollection $fleetUnits, BattleResult $battleResult): UnitCollection
    {
        $survivingUnits = new UnitCollection();

        // For each ship type this fleet sent, calculate how many should survive
        foreach ($fleetUnits->units as $unit) {
            $unitMachineName = $unit->unitObject->machine_name;
            $fleetSentThisType = $unit->amount;

            // Get total sent and total survived for this ship type across all fleets
            $totalSentThisType = $battleResult->attackerUnitsStart->getAmountByMachineName($unitMachineName);
            $totalSurvivedThisType = $battleResult->attackerUnitsResult->getAmountByMachineName($unitMachineName);

            if ($totalSentThisType > 0) {
                // Calculate ship-type-specific survival rate
                $survivalRate = $totalSurvivedThisType / $totalSentThisType;

                // Apply survival rate to what this fleet sent
                $fleetSurvivors = (int)floor($fleetSentThisType * $survivalRate);

                if ($fleetSurvivors > 0) {
                    $survivingUnits->addUnit($unit->unitObject, $fleetSurvivors);
                }
            }
            // else: No ships of this type sent by anyone, so no survivors
        }

        return $survivingUnits;
    }

    /**
     * Process a single fleet attack (fallback when no ACS group is found).
     *
     * @param FleetMission $mission
     * @return void
     * @throws Throwable
     */
    private function processSingleFleetAttack(FleetMission $mission): void
    {
        $defenderPlanet = $this->planetServiceFactory->make($mission->planet_id_to, true);
        $origin_planet = $this->planetServiceFactory->make($mission->planet_id_from, true);

        // Trigger defender planet update to make sure the battle uses up-to-date info.
        $defenderPlanet->update();

        $attackerPlayer = $origin_planet->getPlayer();
        $attackerUnits = $this->fleetMissionService->getFleetUnits($mission);

        // Execute the battle logic using configured battle engine
        switch ($this->settings->battleEngine()) {
            case 'php':
                $battleEngine = new PhpBattleEngine($attackerUnits, $attackerPlayer, $defenderPlanet, $this->settings);
                break;
            case 'rust':
            default:
                $battleEngine = new RustBattleEngine($attackerUnits, $attackerPlayer, $defenderPlanet, $this->settings);
                break;
        }

        $battleResult = $battleEngine->simulateBattle();

        // Deduct loot from the target planet.
        $defenderPlanet->deductResources($battleResult->loot);

        // Calculate total defender losses
        $defenderUnitsLost = clone $battleResult->defenderUnitsStart;
        $defenderUnitsLost->subtractCollection($battleResult->defenderUnitsResult);

        // Calculate ACS Defend units that participated (these are NOT on the planet)
        $acsDefendUnitsStart = new UnitCollection();
        foreach ($battleResult->defendingMissions as $defendingMission) {
            $defendingUnits = $this->fleetMissionService->getFleetUnits($defendingMission);
            $acsDefendUnitsStart->addCollection($defendingUnits);
        }

        // Only remove planet's own losses (exclude ACS Defend units that were in fleet missions)
        // We need to calculate which losses belong to the planet vs ACS Defend fleets
        $planetUnitsLost = new UnitCollection();
        foreach ($defenderUnitsLost->units as $unit) {
            $unitMachineName = $unit->unitObject->machine_name;
            $totalLost = $unit->amount;

            // Get how many of this unit type were from ACS Defend missions
            $acsDefendAmount = $acsDefendUnitsStart->getAmountByMachineName($unitMachineName);

            // The planet can only lose units it actually had, not ACS Defend units
            // So we subtract the ACS Defend starting amount from the losses
            $planetActualLoss = max(0, $totalLost - $acsDefendAmount);

            // Only add to planet losses if planet actually lost units
            if ($planetActualLoss > 0) {
                $planetUnitsLost->addUnit($unit->unitObject, $planetActualLoss);
            }
        }

        $defenderPlanet->removeUnits($planetUnitsLost, false);

        // Calculate repaired defenses (70% chance for each destroyed defense structure)
        $repairedDefenses = $this->calculateRepairedDefenses($defenderUnitsLost);

        // Add repaired defenses back to the planet
        if ($repairedDefenses->getAmount() > 0) {
            $defenderPlanet->addUnits($repairedDefenses, false);
        }

        // Save defenders planet
        $defenderPlanet->save();

        // Create or append debris field.
        $debrisFieldService = resolve(DebrisFieldService::class);
        $debrisFieldService->loadOrCreateForCoordinates($defenderPlanet->getPlanetCoordinates());
        $debrisFieldService->appendResources($battleResult->debris);
        $debrisFieldService->save();

        // Create a moon for defender if result of battle indicates so and defender planet does not already have a moon.
        if (!$defenderPlanet->hasMoon() && $battleResult->moonCreated) {
            $this->planetServiceFactory->createMoonForPlanet($defenderPlanet);
        }

        // Send a message to both attacker and defender with a reference to the same battle report.
        $reportId = $this->createBattleReport($attackerPlayer, $defenderPlanet, $battleResult, $repairedDefenses, false);
        $this->messageService->sendBattleReportMessageToPlayer($attackerPlayer, $reportId);
        $this->messageService->sendBattleReportMessageToPlayer($defenderPlanet->getPlayer(), $reportId);

        // Mark the arrival mission as processed
        $mission->processed = 1;
        $mission->save();

        // Create and start the return mission (if attacker has remaining units).
        $this->startReturn($mission, $battleResult->loot, $battleResult->attackerUnitsResult);
    }

    /**
     * @inheritdoc
     */
    protected function processReturn(FleetMission $mission): void
    {
        // Load the target planet
        $target_planet = $this->planetServiceFactory->make($mission->planet_id_to, true);

        // ACS Attack return trip: add back the units to the source planet.
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
     * Creates a battle report for the given battle result.
     *
     * @param PlayerService $attackPlayer The player who initiated the attack.
     * @param PlanetService $defenderPlanet The planet that was attacked.
     * @param BattleResult $battleResult The result of the battle.
     * @param UnitCollection $repairedDefenses The defensive structures that were repaired after the battle.
     * @param bool $isACSReport Whether this is an ACS battle report.
     * @param array|null $attackerFleets Optional array of attacker fleet data for ACS attacks.
     * @return int
     */
    private function createBattleReport(PlayerService $attackPlayer, PlanetService $defenderPlanet, BattleResult $battleResult, UnitCollection $repairedDefenses, bool $isACSReport = false, ?array $attackerFleets = null): int
    {
        // Create new battle report record.
        $report = new BattleReport();
        $report->planet_galaxy = $defenderPlanet->getPlanetCoordinates()->galaxy;
        $report->planet_system = $defenderPlanet->getPlanetCoordinates()->system;
        $report->planet_position = $defenderPlanet->getPlanetCoordinates()->position;
        $report->planet_type = $defenderPlanet->getPlanetType()->value;

        $report->planet_user_id = $defenderPlanet->getPlayer()->getId();

        $report->general = [
            'moon_existed' => $battleResult->moonExisted,
            'moon_chance' => $battleResult->moonChance,
            'moon_created' => $battleResult->moonCreated,
            'is_acs' => $isACSReport,
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
            'player_id' => $defenderPlanet->getPlayer()->getId(),
            'resource_loss' => $battleResult->defenderResourceLoss->sum(),
            'units' => $battleResult->defenderUnitsStart->toArray(),
            'weapon_technology' => $battleResult->defenderWeaponLevel,
            'shielding_technology' => $battleResult->defenderShieldLevel,
            'armor_technology' => $battleResult->defenderArmorLevel,
        ];

        $report->loot = [
            'percentage' => $battleResult->lootPercentage,
            'metal' => (int)$battleResult->loot->metal->get(),
            'crystal' => (int)$battleResult->loot->crystal->get(),
            'deuterium' => (int)$battleResult->loot->deuterium->get(),
        ];

        $report->debris = [
            'metal' => $battleResult->debris->metal->get(),
            'crystal' => $battleResult->debris->crystal->get(),
            'deuterium' => $battleResult->debris->deuterium->get(),
        ];

        $report->repaired_defenses = $repairedDefenses->toArray();

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

        // Store individual participant data for ACS battles
        if ($isACSReport && $attackerFleets !== null) {
            $acsParticipants = [
                'attackers' => [],
                'defenders' => [],
            ];

            // Store each attacking fleet's data
            foreach ($attackerFleets as $index => $fleet) {
                $player = $fleet['player'];
                $units = $fleet['units'];

                $acsParticipants['attackers'][] = [
                    'player_id' => $player->getId(),
                    'player_name' => $player->getUsername(),
                    'units' => $units->toArray(),
                    'weapon_technology' => $player->getResearchLevel('weapon_technology'),
                    'shielding_technology' => $player->getResearchLevel('shielding_technology'),
                    'armor_technology' => $player->getResearchLevel('armor_technology'),
                ];
            }

            // Calculate ACS Defend units to separate planet units from fleet units
            $acsDefendUnitsStart = new UnitCollection();
            if (!empty($battleResult->defendingMissions)) {
                foreach ($battleResult->defendingMissions as $defendingMission) {
                    $defendingUnits = $this->fleetMissionService->getFleetUnits($defendingMission);
                    $acsDefendUnitsStart->addCollection($defendingUnits);
                }
            }

            // Calculate planet's own units (excluding ACS Defend fleets)
            $planetUnitsStart = clone $battleResult->defenderUnitsStart;
            $planetUnitsStart->subtractCollection($acsDefendUnitsStart);

            // Store planet owner as primary defender with only their planet's units
            $acsParticipants['defenders'][] = [
                'player_id' => $defenderPlanet->getPlayer()->getId(),
                'player_name' => $defenderPlanet->getPlayer()->getUsername(),
                'units' => $planetUnitsStart->toArray(),
                'weapon_technology' => $battleResult->defenderWeaponLevel,
                'shielding_technology' => $battleResult->defenderShieldLevel,
                'armor_technology' => $battleResult->defenderArmorLevel,
                'is_planet_owner' => true,
            ];

            // Store ACS Defend participants with their individual fleet units
            if (!empty($battleResult->defendingMissions)) {
                foreach ($battleResult->defendingMissions as $defendingMission) {
                    $defendingPlayer = resolve(\OGame\Services\PlayerService::class, ['player_id' => $defendingMission->user_id]);
                    $defendingUnits = $this->fleetMissionService->getFleetUnits($defendingMission);

                    $acsParticipants['defenders'][] = [
                        'player_id' => $defendingPlayer->getId(),
                        'player_name' => $defendingPlayer->getUsername(),
                        'units' => $defendingUnits->toArray(),
                        'weapon_technology' => $defendingPlayer->getResearchLevel('weapon_technology'),
                        'shielding_technology' => $defendingPlayer->getResearchLevel('shielding_technology'),
                        'armor_technology' => $defendingPlayer->getResearchLevel('armor_technology'),
                        'is_planet_owner' => false,
                    ];
                }
            }

            $report->acs_participants = $acsParticipants;
        }

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
}
