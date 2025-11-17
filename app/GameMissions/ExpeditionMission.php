<?php

namespace OGame\GameMissions;

use OGame\GameMessages\ExpeditionFailed;
use OGame\GameMessages\ExpeditionFailedAndDelay;
use OGame\GameMessages\ExpeditionFailedAndSpeedup;
use OGame\GameMessages\ExpeditionGainResources;
use OGame\GameMessages\ExpeditionGainShips;
use OGame\GameMessages\ExpeditionGainDarkMatter;
use OGame\GameMessages\ExpeditionGainItem;
use OGame\GameMessages\ExpeditionLossOfFleet;
use OGame\GameMessages\ExpeditionBattlePirates;
use OGame\GameMessages\ExpeditionBattleAliens;
use OGame\GameMissions\Abstracts\GameMission;
use OGame\GameMissions\Models\ExpeditionOutcomeType;
use OGame\GameMissions\Models\MissionPossibleStatus;
use OGame\GameObjects\Models\ShipObject;
use OGame\GameObjects\Models\Units\UnitCollection;
use OGame\Models\Enums\ResourceType;
use OGame\Models\Enums\PlanetType;
use OGame\Models\FleetMission;
use OGame\Models\Planet\Coordinate;
use OGame\Models\Resources;
use OGame\Models\Highscore;
use OGame\Enums\HighscoreTypeEnum;
use OGame\Services\PlanetService;
use OGame\Services\PlayerService;
use OGame\Services\ObjectService;
use OGame\Services\SettingsService;
use Exception;

class ExpeditionMission extends GameMission
{
    protected static string $name = 'Expedition';
    protected static int $typeId = 15;
    protected static bool $hasReturnMission = true;

    /**
     * Get configurable outcome weights from settings.
     * Each outcome has a weight (representing relative probability).
     * Weights are loaded from database settings to allow dynamic event configuration.
     * @return array<string, int>
     */
    protected function getOutcomeWeights(): array
    {
        $settingsService = app(SettingsService::class);

        return [
            'dark_matter' => $settingsService->expeditionWeightDarkMatter(),
            'ships' => $settingsService->expeditionWeightShips(),
            'resources' => $settingsService->expeditionWeightResources(),
            'delay' => $settingsService->expeditionWeightDelay(),
            'speedup' => $settingsService->expeditionWeightSpeedup(),
            'nothing' => $settingsService->expeditionWeightNothing(),
            'black_hole' => $settingsService->expeditionWeightBlackHole(),
            'pirates' => $settingsService->expeditionWeightPirates(),
            'aliens' => $settingsService->expeditionWeightAliens(),
            'merchant' => $settingsService->expeditionWeightMerchant(),
        ];
    }

    /**
     * @inheritdoc
     */
    public function startMissionSanityChecks(PlanetService $planet, Coordinate $targetCoordinate, PlanetType $targetType, UnitCollection $units, Resources $resources): void
    {
        parent::startMissionSanityChecks($planet, $targetCoordinate, $targetType, $units, $resources);

        // Check if there are enough expedition slots available.
        if ($planet->getPlayer()->getExpeditionSlotsInUse() >= $planet->getPlayer()->getExpeditionSlotsMax()) {
            throw new Exception('You are conducting too many expeditions at the same time.');
        }

        // Check if there is at least one non-espionage unit in the fleet.
        if (!$units->hasNonEspionageUnit()) {
            throw new Exception('An expedition must consist of at least one ship.');
        }
    }

    /**
     * @inheritdoc
     */
    public function isMissionPossible(PlanetService $planet, Coordinate $targetCoordinate, PlanetType $targetType, UnitCollection $units): MissionPossibleStatus
    {
        // Expedition mission is only possible for position 16.
        if ($targetCoordinate->position !== 16) {
            return new MissionPossibleStatus(false);
        }

        // Only possible if player has astrophysics research level 1 or higher.
        if ($planet->getPlayer()->getResearchLevel('astrophysics') <= 0) {
            return new MissionPossibleStatus(false, __('Fleets cannot be sent to this target. You have to research Astrophysics first.'));
        }

        // If all checks pass, the mission is possible.
        return new MissionPossibleStatus(true);
    }

    /**
     * Process the arrival of an expedition mission.
     *
     * CRITICAL: Fleet Duplication Prevention
     * ========================================
     * When modifying $units in outcome handlers, be VERY careful to avoid fleet duplication bugs.
     *
     * SAFE patterns:
     * - GainShips: $units->addCollection($foundUnits) - ADDS new ships to existing fleet
     * - LossOfFleet: $units = new UnitCollection() - REPLACES entire fleet with empty
     *
     * DANGEROUS pattern (causes duplication):
     * - Battle: DO NOT do $units->addCollection($battleResult->attackerUnitsResult)
     *   Instead: $units = $battleResult->attackerUnitsResult (REPLACE, not add)
     *
     * Why? Battle engine returns the SURVIVING units. These are already the units that
     * should return. Adding them to the original units would duplicate the survivors.
     *
     * Example:
     * - Original fleet: 100 ships
     * - Battle result: 60 ships survived (returned in attackerUnitsResult)
     * - WRONG: $units->addCollection(60 ships) = 160 ships total (duplicated!)
     * - RIGHT: $units = 60 ships (correct survivors)
     *
     * @inheritdoc
     * @throws \Exception
     */
    protected function processArrival(FleetMission $mission): void
    {
        // Get the units of the mission.
        $units = $this->fleetMissionService->getFleetUnits(mission: $mission);

        $returnResources = new Resources(0, 0, 0, 0);

        // This is the additional time that is added to the return mission's arrival time.
        // This can be either a positive number (delay) or a negative number (speedup).
        $additionalReturnTripTime = 0;

        // If the mission is not processed yet, we need to process the outcome.
        // Select a random outcome based on configuration and weights
        $outcome = $this->selectRandomOutcome();

        switch ($outcome) {
            case ExpeditionOutcomeType::Failed:
                $this->processExpeditionFailedOutcome($mission);
                break;
            case ExpeditionOutcomeType::FailedAndDelay:
                $additionalReturnTripTime = $this->processExpeditionFailedAndDelayOutcome($mission);
                break;
            case ExpeditionOutcomeType::FailedAndSpeedup:
                $additionalReturnTripTime = $this->processExpeditionFailedAndSpeedupOutcome($mission);
                break;
            case ExpeditionOutcomeType::GainShips:
                $foundUnits = $this->processExpeditionGainShipsOutcome($mission);
                if ($foundUnits->getAmount() > 0) {
                    $units->addCollection($foundUnits);
                } else {
                    // No ships could be granted for this fleet -> treat as Failed
                    $this->processExpeditionFailedOutcome($mission);
                }
                break;
            case ExpeditionOutcomeType::GainDarkMatter:
                $this->processExpeditionGainDarkMatterOutcome($mission);
                break;
            case ExpeditionOutcomeType::GainResources:
                $returnResources = $this->processExpeditionGainResourcesOutcome($mission);
                break;
            case ExpeditionOutcomeType::GainMerchantTrade:
                $this->processExpeditionGainMerchantTradeOutcome($mission);
                break;
            case ExpeditionOutcomeType::GainItems:
                $this->processExpeditionGainItemOutcome($mission);
                break;
            case ExpeditionOutcomeType::LossOfFleet:
                $units = $this->processExpeditionLossOfFleetOutcome($mission);
                break;
            case ExpeditionOutcomeType::BattlePirates:
                [$units, $returnResources] = $this->processExpeditionBattleOutcome($mission, 'pirate');
                break;
            case ExpeditionOutcomeType::BattleAliens:
                [$units, $returnResources] = $this->processExpeditionBattleOutcome($mission, 'alien');
                break;
        }

        // Mark the arrival mission as processed
        $mission->processed = 1;
        $mission->save();

        // Create and start the return mission.
        $this->startReturn($mission, $returnResources, $units, $additionalReturnTripTime);
    }

    /**
     * @inheritdoc
     */
    protected function processReturn(FleetMission $mission): void
    {
        $target_planet = $this->planetServiceFactory->make($mission->planet_id_to, true);

        // Expedition mission: add back the units to the source planet.
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
     * Process the expedition failed outcome.
     * @param FleetMission $mission
     * @return void
     */
    private function processExpeditionFailedOutcome(FleetMission $mission): void
    {
        // Load the mission owner user
        $player = $this->playerServiceFactory->make($mission->user_id, true);

        // Send a message to the player with the failure outcome.
        $message_variation_id = ExpeditionFailed::getRandomMessageVariationId();
        $this->messageService->sendSystemMessageToPlayer($player, ExpeditionFailed::class, ['message_variation_id' => $message_variation_id]);
    }

    /**
     * Process the expedition failed and delay outcome.
     * @param FleetMission $mission
     * @return int
     */
    private function processExpeditionFailedAndDelayOutcome(FleetMission $mission): int
    {
        // Load the mission owner user
        $player = $this->playerServiceFactory->make($mission->user_id, true);

        // Define weighted delay factors 2,3,5 probability of 89%, 10%, 1%
        $delayFactors = [
            2 => 89,
            3 => 10,
            5 => 1
        ];

        // Calculate total weight and generate random number
        $totalWeight = array_sum($delayFactors);
        $rand = mt_rand(1, $totalWeight);

        // Select multiplier based on cumulative weight
        $cumulativeWeight = 0;
        $selectedMultiplier = 2; // fallback default

        foreach ($delayFactors as $factor => $weight) {
            $cumulativeWeight += $weight;
            if ($rand <= $cumulativeWeight) {
                $selectedMultiplier = $factor;
                break;
            }
        }

        // Calculate base additional return trip time based on holding time
        // Formula: Base Delay = Delay factor × Holding time
        $baseAdditionalReturnTripTime = $mission->time_holding * $selectedMultiplier;

        // Apply universe fleet speed modifier
        // Formula: Actual Delay = Base Delay / Fleet Speed
        $fleetSpeed = $this->settings->fleetSpeed();
        $additionalReturnTripTime = intval($baseAdditionalReturnTripTime / $fleetSpeed);

        // Send a message to the player with the failure and delay outcome
        $message_variation_id = ExpeditionFailedAndDelay::getRandomMessageVariationId();
        $this->messageService->sendSystemMessageToPlayer($player, ExpeditionFailedAndDelay::class, ['message_variation_id' => $message_variation_id]);

        return $additionalReturnTripTime;
    }

    /**
     * Process the expedition failed and speedup outcome.
     * @param FleetMission $mission
     * @return int
     */
    private function processExpeditionFailedAndSpeedupOutcome(FleetMission $mission): int
    {
        // Load the mission owner user
        $player = $this->playerServiceFactory->make($mission->user_id, true);

        // Pick a random speedup percentage between 5% and 30%.
        $additionalReturnTripTimePercentage = random_int(5, 10);

        // Calculate one way mission duration.
        $onewayMissionDuration = ($mission->time_arrival - $mission->time_departure) + $mission->time_holding;

        // Calculate the subtraction from the return trip time in seconds based on the mission's original duration + holding time.
        // Note: this value is negative on purpose, so it will be subtracted from the default return trip time.
        $additionalReturnTripTime = -intval($onewayMissionDuration * ($additionalReturnTripTimePercentage / 100));

        // Send a message to the player with the failure and speedup outcome.
        $message_variation_id = ExpeditionFailedAndSpeedup::getRandomMessageVariationId();
        $this->messageService->sendSystemMessageToPlayer($player, ExpeditionFailedAndSpeedup::class, ['message_variation_id' => $message_variation_id]);

        return $additionalReturnTripTime;
    }

    /**
     * Process the expedition gain resources outcome.
     * @param FleetMission $mission
     * @return Resources
     */
    private function processExpeditionGainResourcesOutcome(FleetMission $mission): Resources
    {
        // Load the mission owner user
        $player = $this->playerServiceFactory->make($mission->user_id, true);
        $resourcesFound = new Resources(0, 0, 0, 0);

        // Get max cargo capacity of the fleet.
        $units = $this->fleetMissionService->getFleetUnits(mission: $mission);
        $maxCargoCapacity = $units->getTotalCargoCapacity($player);

        // Determine the max resource find.
        $maxResourceFind = $this->determineMaxResourceFind();

        // Determine the resource type: metal, crystal or deuterium.
        $cargoCapacityConstrainedAmount = 0;
        $resource_type_int = random_int(0, 2);
        switch ($resource_type_int) {
            case 0:
                $resource_type = ResourceType::Metal;

                $cargoCapacityConstrainedAmount = min($maxCargoCapacity, $maxResourceFind);
                $resourcesFound->metal->set($cargoCapacityConstrainedAmount);
                break;
            case 1:
                $resource_type = ResourceType::Crystal;

                $adjustedMaxResourceFind = $maxResourceFind * (2 / 3);
                $cargoCapacityConstrainedAmount = min($maxCargoCapacity, $adjustedMaxResourceFind);
                $resourcesFound->crystal->set($cargoCapacityConstrainedAmount);
                break;
            case 2:
                $resource_type = ResourceType::Deuterium;

                $adjustedMaxResourceFind = $maxResourceFind * (1 / 3);
                $cargoCapacityConstrainedAmount = min($maxCargoCapacity, $adjustedMaxResourceFind);
                $resourcesFound->deuterium->set($cargoCapacityConstrainedAmount);
                break;
        }

        // Send a message to the player with the resources found outcome.
        // Choose a random message variation id based on the number of available outcomes.
        $message_variation_id = ExpeditionGainResources::getRandomMessageVariationId();
        $this->messageService->sendSystemMessageToPlayer($player, ExpeditionGainResources::class, ['message_variation_id' => $message_variation_id, 'resource_type' => $resource_type->value, 'resource_amount' => $cargoCapacityConstrainedAmount]);

        return $resourcesFound;
    }

    /**
     * Process the expedition gain ships outcome.
     * @param FleetMission $mission
     * @return UnitCollection
     */
    private function processExpeditionGainShipsOutcome(FleetMission $mission): UnitCollection
    {
        // Load the mission owner user
        $player = $this->playerServiceFactory->make($mission->user_id, true);

        // Get the expedition fleet units to determine what ships can be found
        $fleetUnits = $this->fleetMissionService->getFleetUnits(mission: $mission);
        $objectService = app(ObjectService::class);

        // Define expedition hierarchy levels (eligibility + ceiling).
        // TODO: when implementing pathfinder and reaper units, add them to the levels array.
        // Pathfinder = between cruiser and battle_ship, Reaper = after destroyer.
        $expeditionLevels = [
            1 => ['small_cargo', 'light_fighter', 'espionage_probe'],
            2 => ['large_cargo'],
            3 => ['heavy_fighter'],
            4 => ['cruiser'],
            5 => ['battle_ship'],
            6 => ['battlecruiser'],
            7 => ['bomber'],
            8 => ['destroyer'],
        ];

        // Helper function to find which level a ship belongs to
        $getShipLevel = function ($shipMachineName) use ($expeditionLevels) {
            foreach ($expeditionLevels as $level => $ships) {
                if (in_array($shipMachineName, $ships)) {
                    return $level;
                }
            }
            return 0;
        };

        // Find the highest expedition level in the fleet
        $maxExpeditionLevel = 0;
        foreach ($fleetUnits->units as $unit) {
            if ($unit->unitObject instanceof ShipObject) {
                $shipMachineName = $unit->unitObject->machine_name;
                $shipLevel = $getShipLevel($shipMachineName);
                if ($shipLevel > 0) {
                    $maxExpeditionLevel = max($maxExpeditionLevel, $shipLevel);
                }
            }
        }

        // If no expedition-capable ships found, default to level 1
        if ($maxExpeditionLevel === 0) {
            $maxExpeditionLevel = 1;
        }

        // Collect all ships that can be found up to one tier above the highest tier present,
        // capped at the highest defined tier.
        $highestDefinedLevel = max(array_keys($expeditionLevels));
        $maxFindLevel = min($maxExpeditionLevel + 1, $highestDefinedLevel);

        $possibleShipMachineNames = [];
        for ($level = 1; $level <= $maxFindLevel; $level++) {
            $possibleShipMachineNames = array_merge($possibleShipMachineNames, $expeditionLevels[$level]);
        }

        // Get ship objects for the possible ships
        $possibleShips = [];
        foreach ($possibleShipMachineNames as $machineName) {
            try {
                $ship = $objectService->getShipObjectByMachineName($machineName);
                $possibleShips[] = $ship;
            } catch (Exception $e) {
                // Ship not found. Skip it.
                continue;
            }
        }

        // If no ships can be found at all for this fleet composition, just return empty.
        // The caller will decide how to message (fallback to Failed).
        if (empty($possibleShips)) {
            return new UnitCollection();
        }

        // Get max cargo capacity of the fleet.
        $units = $this->fleetMissionService->getFleetUnits(mission: $mission);
        $maxCargoCapacity = $units->getTotalCargoCapacity($player);

        // Determine the max resource find.
        $maxResourceFind = $this->determineMaxResourceFind();
        $cargoCapacityConstrainedAmount = min($maxCargoCapacity, $maxResourceFind);

        // Select 1-6 random ship types from possible ships.
        $num_ship_types = min(random_int(1, 6), count($possibleShips));
        shuffle($possibleShips);
        $selectedShips = array_slice($possibleShips, 0, $num_ship_types);

        // Distribute resources per ship type with randomness (up to 75% variance), last ship gets the remainder.
        // E.g. if there are 500k resources to find and 3 ship types, the actual ship amount found might look like this:
        // Ship type 1: 150k / 4k cost per ship = 37 ships (rounded down)
        // Ship type 2: 250k / 10k cost per ship = 25 ships
        // Ship type 3: 100k / 20k cost per ship = 5 ships
        // The distribution is random, so it will look different each time.
        $remainingResources = $cargoCapacityConstrainedAmount;
        $numShips = count($selectedShips);
        $averageResourcePerShip = $cargoCapacityConstrainedAmount / $numShips;

        $units = new UnitCollection();
        foreach ($selectedShips as $i => $ship) {
            $shipPrice = $ship->price->resources->sum();

            if ($i < $numShips - 1) {
                // For all but the last ship, allocate a random portion (25% to 175%) of the average share
                $minResources = $averageResourcePerShip * 0.25;
                $maxResources = $averageResourcePerShip * 1.75;
                $randomResources = min(
                    $remainingResources,
                    random_int((int)round($minResources), (int)round($maxResources))
                );
            } else {
                // Last ship gets all remaining resources
                $randomResources = $remainingResources;
            }

            $amount = (int)floor($randomResources / $shipPrice);
            if ($amount > 0) {
                $units->addUnit($ship, $amount);
            }

            $remainingResources -= $randomResources;
            if ($remainingResources < 0) {
                $remainingResources = 0;
            }
        }

        if (empty($units->units)) {
            $cheapest = $units->findCheapestShip($possibleShips);

            if ($cheapest !== null && $cargoCapacityConstrainedAmount >= $cheapest->price->resources->sum()) {
                $units->addUnit($cheapest, 1);
            }
        }

        // If still empty here (no eligible or affordable ships), return empty.
        // Caller will handle fallback messaging.
        if (empty($units->units)) {
            return new UnitCollection();
        }

        // Convert units to array with key "unit_<unit_id>" and value as amount.
        $message_params = [];
        foreach ($units->units as $unit) {
            $message_params['unit_' . $unit->unitObject->id] = $unit->amount;
        }

        // Send a message to the player with the units found outcome.
        // Choose a random message variation id based on the number of available outcomes.
        $message_variation_id = ExpeditionGainShips::getRandomMessageVariationId();
        $this->messageService->sendSystemMessageToPlayer($player, ExpeditionGainShips::class, ['message_variation_id' => $message_variation_id] + $message_params);

        return $units;
    }

    /**
     * Process the expedition gain dark matter outcome.
     * @param FleetMission $mission
     * @return void
     */
    private function processExpeditionGainDarkMatterOutcome(FleetMission $mission): void
    {
        // Load the mission owner user
        $player = $this->playerServiceFactory->make($mission->user_id, true);

        // TODO: Add actual dark matter to player when dark matter itself is implemented.

        $message_variation_id = ExpeditionGainDarkMatter::getRandomMessageVariationId();
        $this->messageService->sendSystemMessageToPlayer($player, ExpeditionGainDarkMatter::class, ['message_variation_id' => $message_variation_id]);
    }

    /**
     * Process the expedition gain merchant trade outcome.
     * @param FleetMission $mission
     * @return void
     */
    private function processExpeditionGainMerchantTradeOutcome(FleetMission $mission): void
    {
        // TODO: Implement merchant trade logic
        $player = $this->playerServiceFactory->make($mission->user_id, true);

        // TODO: Send appropriate message once ExpeditionMerchantTrade message class exists
        // $message_variation_id = ExpeditionMerchantTrade::getRandomMessageVariationId();
        // $this->messageService->sendSystemMessageToPlayer($player, ExpeditionMerchantTrade::class, ['message_variation_id' => $message_variation_id]);
    }

    /**
     * Process the expedition gain item outcome.
     * @param FleetMission $mission
     * @return void
     */
    private function processExpeditionGainItemOutcome(FleetMission $mission): void
    {
        // Load the mission owner user
        $player = $this->playerServiceFactory->make($mission->user_id, true);

        // TODO: Implement actual item giving logic when items themselves are implemented.

        // Send a message to the player with the item found outcome.
        $message_variation_id = ExpeditionGainItem::getRandomMessageVariationId();
        $this->messageService->sendSystemMessageToPlayer($player, ExpeditionGainItem::class, ['message_variation_id' => $message_variation_id]);
    }

    /**
     * Process the expedition loss of fleet outcome.
     * @param FleetMission $mission
     * @return UnitCollection
     */
    private function processExpeditionLossOfFleetOutcome(FleetMission $mission): UnitCollection
    {
        // Load the mission owner user
        $player = $this->playerServiceFactory->make($mission->user_id, true);

        // Send a message to the player with the fleet destroyed outcome.
        $message_variation_id = ExpeditionLossOfFleet::getRandomMessageVariationId();
        $this->messageService->sendSystemMessageToPlayer($player, ExpeditionLossOfFleet::class, ['message_variation_id' => $message_variation_id]);

        // Return empty unit collection as the whole fleet is destroyed.
        return new UnitCollection();
    }

    /**
     * Process the expedition battle outcome (pirates or aliens).
     *
     * CRITICAL: This method returns SURVIVING units from battle, not additions.
     * The caller MUST use: $units = $battleResult[0]  (REPLACE, not add)
     * DO NOT use: $units->addCollection() - this causes fleet duplication!
     *
     * @param FleetMission $mission
     * @param string $npcType 'pirate' or 'alien'
     * @return array{UnitCollection, Resources} [surviving units, loot resources]
     */
    private function processExpeditionBattleOutcome(FleetMission $mission, string $npcType): array
    {
        // Load the mission owner
        $player = $this->playerServiceFactory->make($mission->user_id, true);

        // Get player's expedition fleet
        $playerFleet = $this->fleetMissionService->getFleetUnits($mission);

        // Generate NPC fleet
        $npcFleetGenerator = app(\OGame\Services\NPCFleetGeneratorService::class);
        $npcData = $npcFleetGenerator->generateEnemyFleet($playerFleet, $player, $npcType);
        $npcFleet = $npcData['fleet'];
        $npcPlayer = $npcData['player'];

        // Create a temporary "planet" for the NPC (battle engine expects a planet)
        // Since expedition battles happen in deep space (position 16) which has no real planet,
        // we'll use the player's home planet as a temporary location to add NPC ships
        // and run the battle. This is just for battle engine compatibility.
        $originPlanet = $this->planetServiceFactory->make($mission->planet_id_from, true);

        if ($originPlanet === null) {
            // Fallback: try to get player's first planet
            $originPlanet = $player->planets->first();
            if ($originPlanet === null) {
                throw new \Exception('Cannot create expedition battle: no valid planet for player');
            }
        }

        // Create NPC planet service for the battle
        // This properly extends PlanetService so it satisfies the RustBattleEngine type requirement
        // We use the origin planet's ID so the NPC service has a valid planet for coordinates/ID
        $npcPlanetService = new \OGame\Services\NPCPlanetService(
            $this->playerServiceFactory,
            $this->settings,
            $npcPlayer,
            $npcFleet,
            $originPlanet->getPlanetId()
        );

        // Run the battle with player as attacker and NPC planet as defender
        $battleEngine = new \OGame\GameMissions\BattleEngine\RustBattleEngine(
            $playerFleet,
            $player,
            $npcPlanetService,
            $this->settings
        );

        $battleResult = $battleEngine->simulateBattle();

        // Create battle report for expedition battle
        // Pass expedition coordinates (position 16) not the temporary planet coordinates
        $expeditionCoords = new \OGame\Models\Planet\Coordinate(
            $mission->galaxy_to,
            $mission->system_to,
            16
        );
        $reportId = $this->createExpeditionBattleReport($player, $npcPlayer, $expeditionCoords, $battleResult);

        // Process battle result
        $survivingUnits = $battleResult->attackerUnitsResult;

        // No loot generated from pirate/alien battles - it's purely a combat event
        $loot = new Resources(0, 0, 0, 0);

        // Send battle report message to player
        $this->messageService->sendBattleReportMessageToPlayer($player, $reportId);

        // Send expedition battle outcome message (pirate or alien specific) with battle_report_id
        if ($npcType === 'pirate') {
            $message_variation_id = ExpeditionBattlePirates::getRandomMessageVariationId();
            $this->sendExpeditionBattleMessage($player, ExpeditionBattlePirates::class, $message_variation_id, $reportId);
        } else {
            $message_variation_id = ExpeditionBattleAliens::getRandomMessageVariationId();
            $this->sendExpeditionBattleMessage($player, ExpeditionBattleAliens::class, $message_variation_id, $reportId);
        }

        // CRITICAL: Return surviving units (not added to original)
        // The caller will REPLACE $units with this, not add to it
        return [$survivingUnits, $loot];
    }

    /**
     * Send an expedition battle message with battle_report_id attached.
     *
     * @param PlayerService $player
     * @param string $gameMessageClass
     * @param int $messageVariationId
     * @param int $battleReportId
     * @return void
     */
    private function sendExpeditionBattleMessage(PlayerService $player, string $gameMessageClass, int $messageVariationId, int $battleReportId): void
    {
        try {
            // Create a temporary message to instantiate the GameMessage class and get metadata
            $tempMessage = new \OGame\Models\Message();
            $tempMessage->user_id = $player->getId();
            $tempMessage->params = ['message_variation_id' => $messageVariationId];
            $tempMessage->battle_report_id = $battleReportId;

            /** @var \OGame\GameMessages\Abstracts\GameMessage $gameMessage */
            $gameMessage = app($gameMessageClass, ['message' => $tempMessage]);

            // Now create the actual message with all the metadata and battle_report_id
            $message = new \OGame\Models\Message();
            $message->user_id = $player->getId();
            $message->key = $gameMessage->getKey();
            $message->tab = $gameMessage->getTab();
            $message->subtab = $gameMessage->getSubtab();
            $message->params = ['message_variation_id' => $messageVariationId];
            $message->battle_report_id = $battleReportId;
            $message->save();
        } catch (\Exception $e) {
            throw new \RuntimeException('Could not create expedition battle message: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Calculate total value of a fleet.
     *
     * @param UnitCollection $fleet
     * @return int
     */
    private function calculateFleetValue(UnitCollection $fleet): int
    {
        $totalValue = 0;
        foreach ($fleet->units as $unit) {
            $shipCost = $unit->unitObject->price->resources->sum();
            $totalValue += $shipCost * $unit->amount;
        }
        return $totalValue;
    }

    /**
     * Create a battle report for an expedition battle against pirates or aliens.
     *
     * @param PlayerService $player The player who sent the expedition
     * @param NPCPlayerService $npcPlayer The NPC opponent (pirate or alien)
     * @param \OGame\Models\Planet\Coordinate $expeditionCoords The expedition coordinates (position 16)
     * @param \OGame\GameMissions\BattleEngine\Models\BattleResult $battleResult The result of the battle
     * @return int The battle report ID
     */
    private function createExpeditionBattleReport(PlayerService $player, \OGame\Services\NPCPlayerService $npcPlayer, \OGame\Models\Planet\Coordinate $expeditionCoords, \OGame\GameMissions\BattleEngine\Models\BattleResult $battleResult): int
    {
        // Create new battle report record
        $report = new \OGame\Models\BattleReport();
        $report->planet_galaxy = $expeditionCoords->galaxy;
        $report->planet_system = $expeditionCoords->system;
        $report->planet_position = $expeditionCoords->position;
        $report->planet_type = \OGame\Models\Enums\PlanetType::Planet->value;
        $report->planet_user_id = $player->getId(); // Player is the defender

        $report->general = [
            'moon_existed' => false,
            'moon_chance' => 0,
            'moon_created' => false,
            'expedition_battle' => true,
            'npc_type' => $npcPlayer->getNpcType(),
        ];

        // SWAP attacker and defender: In expeditions, NPCs attack the player's fleet
        $report->attacker = [
            'player_id' => $npcPlayer->getId(),
            'player_name' => $npcPlayer->getUsername(), // "Space Pirates" or "Alien Fleet"
            'resource_loss' => $battleResult->defenderResourceLoss->sum(),
            'units' => $battleResult->defenderUnitsStart->toArray(),
            'weapon_technology' => $battleResult->defenderWeaponLevel,
            'shielding_technology' => $battleResult->defenderShieldLevel,
            'armor_technology' => $battleResult->defenderArmorLevel,
        ];

        $report->defender = [
            'player_id' => $player->getId(),
            'resource_loss' => $battleResult->attackerResourceLoss->sum(),
            'units' => $battleResult->attackerUnitsStart->toArray(),
            'weapon_technology' => $battleResult->attackerWeaponLevel,
            'shielding_technology' => $battleResult->attackerShieldLevel,
            'armor_technology' => $battleResult->attackerArmorLevel,
        ];

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

        $report->repaired_defenses = []; // No defenses in expeditions

        // SWAP round data: NPC is attacker, player is defender
        $rounds = [];
        foreach ($battleResult->rounds as $round) {
            $rounds[] = [
                'attacker_ships' => $round->defenderShips->toArray(),      // NPC ships (were defender in engine)
                'defender_ships' => $round->attackerShips->toArray(),      // Player ships (were attacker in engine)
                'attacker_losses' => $round->defenderLosses->toArray(),    // NPC losses
                'defender_losses' => $round->attackerLosses->toArray(),    // Player losses
                'attacker_losses_in_this_round' => $round->defenderLossesInRound->toArray(),
                'defender_losses_in_this_round' => $round->attackerLossesInRound->toArray(),
                'absorbed_damage_attacker' => $round->absorbedDamageDefender,
                'absorbed_damage_defender' => $round->absorbedDamageAttacker,
                'full_strength_attacker' => $round->fullStrengthDefender,
                'full_strength_defender' => $round->fullStrengthAttacker,
                'hits_attacker' => $round->hitsDefender,
                'hits_defender' => $round->hitsAttacker,
            ];
        }

        $report->rounds = $rounds;
        $report->save();

        return $report->id;
    }

    /**
     * Select a random expedition outcome based on configured weights. Higher weight
     * for a particular outcome means more chance of that outcome being selected
     * relative to the other outcomes.
     *
     * @return ExpeditionOutcomeType
     */
    private function selectRandomOutcome(): ExpeditionOutcomeType
    {
        $settingsService = app(SettingsService::class);

        // Map outcome types to their weights
        $outcomeMapping = [
            'dark_matter' => ExpeditionOutcomeType::GainDarkMatter,
            'ships' => ExpeditionOutcomeType::GainShips,
            'resources' => ExpeditionOutcomeType::GainResources,
            'delay' => ExpeditionOutcomeType::FailedAndDelay,
            'speedup' => ExpeditionOutcomeType::FailedAndSpeedup,
            'nothing' => ExpeditionOutcomeType::Failed,
            'black_hole' => ExpeditionOutcomeType::LossOfFleet,
            'pirates' => ExpeditionOutcomeType::BattlePirates,
            'aliens' => ExpeditionOutcomeType::BattleAliens,
            'merchant' => ExpeditionOutcomeType::GainMerchantTrade,
        ];

        // Build weighted array of enabled outcomes
        $weightedOutcomes = [];
        $totalWeight = 0;

        // Get weights from settings (allows dynamic event configuration)
        $outcomeWeights = $this->getOutcomeWeights();

        foreach ($outcomeWeights as $key => $weight) {
            if (!isset($outcomeMapping[$key])) {
                continue;
            }

            // Skip outcomes with 0 weight (disabled)
            if ($weight <= 0) {
                continue;
            }

            $outcome = $outcomeMapping[$key];

            // TODO: Remove this filter once outcomes are fully implemented
            // For now, skip unimplemented outcomes
            if (in_array($outcome, [
                ExpeditionOutcomeType::GainDarkMatter,
                ExpeditionOutcomeType::GainItems,
                ExpeditionOutcomeType::GainMerchantTrade,
                ExpeditionOutcomeType::Battle,
            ])) {
                continue;
            }

            $weightedOutcomes[] = [
                'outcome' => $outcome,
                'weight' => $weight,
            ];
            $totalWeight += $weight;
        }

        // If no outcomes are enabled, default to failure
        if (empty($weightedOutcomes)) {
            return ExpeditionOutcomeType::Failed;
        }

        // Pick a random number between 1 and total weight
        $random = random_int(1, $totalWeight);

        // Find which outcome was selected
        $currentWeight = 0;
        foreach ($weightedOutcomes as $weighted) {
            $currentWeight += $weighted['weight'];
            if ($random <= $currentWeight) {
                return $weighted['outcome'];
            }
        }

        // Fallback (should never reach here)
        return ExpeditionOutcomeType::Failed;
    }

    /**
     * Get the max resource find based on the rank 1 highscore points and by returning a random
     * value between 10% and 100% of the actual max resource find for variance.
     *
     * This method is used by both gainResources and gainShips outcome.
     *
     * @return int
     */
    private function determineMaxResourceFind(): int
    {
        // Max resources found is according to these params:
        // Number 1 player highscore "general" points determines the max resources found:
        // < 10.000 points: 40.000 metal
        // < 100.000 points: 500.000 metal
        // < 1.000.000 points: 1.200.000 metal
        // < 5.000.000 points: 1.800.000 metal
        // < 25.000.000 points: 2.400.000 metal
        // < 50.000.000 points: 3.000.000 metal
        // < 75.000.000 points: 3.600.000 metal
        // < 100.000.000 points: 4.200.000 metal
        // > 100.000.000 points: 5.000.000 metal
        // When discoverer class is active, the following modifier applies: economy speed * 1.5.
        // E.g. with discoverer class and economy speed 5x, the modifier is 7.5.
        // so the >100m points max find would be 7.5 * 5m = 37.5m
        // Crystal is 2/3 of the metal find.
        // Deuterium is 1/3 of the metal find.
        $rank_1_highscore_points = Highscore::orderByDesc(HighscoreTypeEnum::general->name)->first()->general;

        if ($rank_1_highscore_points < 10000) {
            $max = 40000;
        } elseif ($rank_1_highscore_points < 100000) {
            $max = 500000;
        } elseif ($rank_1_highscore_points < 1000000) {
            $max = 1200000;
        } elseif ($rank_1_highscore_points < 5000000) {
            $max = 1800000;
        } elseif ($rank_1_highscore_points < 25000000) {
            $max = 2400000;
        } elseif ($rank_1_highscore_points < 50000000) {
            $max = 3000000;
        } elseif ($rank_1_highscore_points < 75000000) {
            $max = 3600000;
        } elseif ($rank_1_highscore_points < 100000000) {
            $max = 4200000;
        } else {
            $max = 5000000;
        }

        // Set min to at least 10% of the max.
        $min = max(1, (int)floor($max * 0.1));

        // Pick a random amount between min and max.
        $resourceAmount = random_int($min, $max);

        // TODO: when actual player classes such as discoverer, collector etc. are implemented, make this modifier apply only if class is "discoverer".
        // For now we apply it anyway so the economy speed is applied to the resource find.
        $economySpeed = $this->settings->economySpeed();
        $resourceAmount = $resourceAmount * ($economySpeed * 1.5);

        // TODO: when pathfinder unit is added to the game and included in the fleet, the max find should be doubled.

        // Apply expedition rewards multiplier (for timed events)
        $settingsService = app(SettingsService::class);
        $rewardsMultiplier = $settingsService->expeditionRewardsMultiplier();
        $resourceAmount = $resourceAmount * $rewardsMultiplier;

        return (int)$resourceAmount;
    }
}
