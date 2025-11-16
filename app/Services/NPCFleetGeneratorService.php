<?php

namespace OGame\Services;

use OGame\GameObjects\Models\Units\UnitCollection;

/**
 * NPCFleetGeneratorService - Generates NPC fleets for expedition battles.
 *
 * This service creates balanced enemy fleets (pirates or aliens) based on:
 * - Player fleet composition and strength
 * - NPC type (pirates have lower tech, aliens have higher tech)
 * - Random variation to keep battles unpredictable
 */
class NPCFleetGeneratorService
{
    /**
     * Generate an enemy fleet for an expedition battle.
     *
     * @param UnitCollection $playerFleet The player's expedition fleet
     * @param PlayerService $playerService The player service for tech level comparison
     * @param string $npcType 'pirate' or 'alien'
     * @return array{fleet: UnitCollection, player: NPCPlayerService}
     */
    public function generateEnemyFleet(UnitCollection $playerFleet, PlayerService $playerService, string $npcType): array
    {
        $objectService = app(ObjectService::class);

        // Calculate total fleet value (sum of ship costs)
        $playerFleetValue = $this->calculateFleetValue($playerFleet);

        // Determine battle size tier:
        // Level 1 (89%): Normal battle
        // Level 2 (10%): Large battle
        // Level 3 (1%): Extra-large battle
        $battleSizeTier = $this->selectBattleSizeTier();

        // Generate tech levels based on player's tech
        [$weaponTech, $shieldTech, $armorTech] = $this->generateNPCTechLevels($playerService, $npcType);

        // Create NPC player service
        $npcPlayer = new NPCPlayerService($npcType, $weaponTech, $shieldTech, $armorTech);

        // Generate fleet composition based on tier
        $npcFleet = $this->generateFleetComposition($playerFleetValue, $npcType, $battleSizeTier);

        return [
            'fleet' => $npcFleet,
            'player' => $npcPlayer,
        ];
    }

    /**
     * Calculate the total value of a fleet (sum of ship costs).
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
     * Generate NPC tech levels based on player's tech.
     *
     * Pirates: Player tech level - 3 (minimum 0)
     * Aliens: Player tech level + 3
     *
     * At level 0: Pirates have 70% effectiveness, Aliens have 130%
     * At level 10: Pirates have 85% effectiveness, Aliens have 115%
     *
     * @param PlayerService $playerService
     * @param string $npcType
     * @return array{int, int, int} [weapon, shield, armor]
     */
    private function generateNPCTechLevels(PlayerService $playerService, string $npcType): array
    {
        $playerWeapon = $playerService->getResearchLevel('weapon_technology');
        $playerShield = $playerService->getResearchLevel('shielding_technology');
        $playerArmor = $playerService->getResearchLevel('armor_technology');

        if ($npcType === 'pirate') {
            // Pirates: Player tech - 3 (minimum 0)
            $weaponTech = max(0, $playerWeapon - 3);
            $shieldTech = max(0, $playerShield - 3);
            $armorTech = max(0, $playerArmor - 3);
        } else {
            // Aliens: Player tech + 3
            $weaponTech = $playerWeapon + 3;
            $shieldTech = $playerShield + 3;
            $armorTech = $playerArmor + 3;
        }

        return [$weaponTech, $shieldTech, $armorTech];
    }

    /**
     * Select battle size tier based on OGame probabilities.
     *
     * Level 1 (89%): Normal battle
     * Level 2 (10%): Large battle
     * Level 3 (1%): Extra-large battle
     *
     * @return int Battle size tier (1, 2, or 3)
     */
    private function selectBattleSizeTier(): int
    {
        $random = random_int(1, 100);

        if ($random <= 89) {
            return 1; // Normal (89%)
        } elseif ($random <= 99) {
            return 2; // Large (10%)
        } else {
            return 3; // Extra-large (1%)
        }
    }

    /**
     * Generate fleet composition based on OGame specifications.
     *
     * Fleet composition = Bonus combat ships + Cargo ships
     * - Bonus ships provide ALL combat power
     * - Fleet value percentage is spent entirely on cargo ships
     *
     * Composition depends on battle size tier:
     * - Level 1 (89%): Pirates 33% cargo + 5 LF, Aliens 44% cargo + 5 HF
     * - Level 2 (10%): Pirates 55% cargo + 3 Cruisers, Aliens 66% cargo + 3 Battlecruisers
     * - Level 3 (1%): Pirates 88% cargo + 2 Battleships, Aliens 99% cargo + 2 Destroyers
     *
     * @param int $playerFleetValue Total value of player's fleet
     * @param string $npcType 'pirate' or 'alien'
     * @param int $battleSizeTier Battle size (1, 2, or 3)
     * @return UnitCollection
     */
    private function generateFleetComposition(int $playerFleetValue, string $npcType, int $battleSizeTier): UnitCollection
    {
        $objectService = app(ObjectService::class);
        $npcFleet = new UnitCollection();

        // Determine fleet value percentage and bonus ship based on tier and type
        if ($battleSizeTier === 1) {
            // Level 1 (89%): Normal battle
            $fleetValuePercentage = $npcType === 'pirate' ? 0.33 : 0.44;
            $bonusShipType = $npcType === 'pirate' ? 'light_fighter' : 'heavy_fighter';
            $bonusShipCount = 5;
        } elseif ($battleSizeTier === 2) {
            // Level 2 (10%): Large battle
            $fleetValuePercentage = $npcType === 'pirate' ? 0.55 : 0.66;
            $bonusShipType = $npcType === 'pirate' ? 'cruiser' : 'battlecruiser';
            $bonusShipCount = 3;
        } else {
            // Level 3 (1%): Extra-large battle
            $fleetValuePercentage = $npcType === 'pirate' ? 0.88 : 0.99;
            $bonusShipType = $npcType === 'pirate' ? 'battle_ship' : 'destroyer';
            $bonusShipCount = 2;
        }

        // Calculate total NPC fleet value
        $npcFleetValue = (int)($playerFleetValue * $fleetValuePercentage);

        // Generate main fleet with mixed ship types
        $npcFleet = $this->generateMixedFleet($npcFleetValue, $npcType);

        // Add bonus ships
        try {
            $bonusShip = $objectService->getShipObjectByMachineName($bonusShipType);
            $npcFleet->addUnit($bonusShip, $bonusShipCount);
        } catch (\Exception $e) {
            // Bonus ship not found, continue without it
        }

        return $npcFleet;
    }

    /**
     * Generate a fleet of cargo ships (and for aliens, some combat ships) based on allocated value.
     *
     * Pirates: Fleet value spent entirely on cargo ships
     * Aliens: Fleet value mostly on cargo, but can include some combat ships (bombers, battlecruisers)
     *
     * @param int $allocatedValue Total value to spend on ships
     * @param string $npcType 'pirate' or 'alien'
     * @return UnitCollection
     */
    private function generateMixedFleet(int $allocatedValue, string $npcType): UnitCollection
    {
        $objectService = app(ObjectService::class);
        $npcFleet = new UnitCollection();

        if ($npcType === 'pirate') {
            // Pirates: Only cargo ships
            $possibleShips = [
                'small_cargo' => 60,     // Common
                'large_cargo' => 40,     // Less common
            ];
        } else {
            // Aliens: Mostly cargo, but can have some combat ships too
            $possibleShips = [
                'small_cargo' => 50,     // Common
                'large_cargo' => 35,     // Common
                'espionage_probe' => 10, // Uncommon
                'battlecruiser' => 3,    // Rare
                'bomber' => 2,           // Rare
            ];
        }

        // Select 1-2 ship types for pirates, 1-3 for aliens
        $selectedShipCount = $npcType === 'pirate' ? random_int(1, 2) : random_int(1, 3);
        $selectedShips = $this->weightedRandomSelection($possibleShips, $selectedShipCount);

        // Distribute allocated value across selected ships
        $remainingValue = $allocatedValue;
        $shipTypeCount = count($selectedShips);

        foreach ($selectedShips as $index => $shipMachineName) {
            try {
                $ship = $objectService->getShipObjectByMachineName($shipMachineName);
                $shipCost = $ship->price->resources->sum();

                // For last ship, use all remaining value
                if ($index === $shipTypeCount - 1) {
                    $allocatedForThisShip = $remainingValue;
                } else {
                    // Randomly allocate 20-50% of remaining value
                    $allocationPercentage = random_int(20, 50) / 100;
                    $allocatedForThisShip = (int)($remainingValue * $allocationPercentage);
                }

                // Calculate how many ships we can afford
                $shipCount = (int)floor($allocatedForThisShip / $shipCost);

                if ($shipCount > 0) {
                    $npcFleet->addUnit($ship, $shipCount);
                    $remainingValue -= ($shipCount * $shipCost);
                }
            } catch (\Exception $e) {
                // Ship not found, skip
                continue;
            }
        }

        // Ensure NPC has at least some ships (fallback to light fighters)
        if ($npcFleet->getAmount() === 0) {
            try {
                $lightFighter = $objectService->getShipObjectByMachineName('light_fighter');
                $lightFighterCost = $lightFighter->price->resources->sum();
                $shipCount = max(1, (int)floor($allocatedValue / $lightFighterCost));
                $npcFleet->addUnit($lightFighter, $shipCount);
            } catch (\Exception $e) {
                // Even light fighter failed, return empty fleet (should never happen)
            }
        }

        return $npcFleet;
    }

    /**
     * Perform weighted random selection of items.
     *
     * @param array<string, int> $items Item => weight mapping
     * @param int $count Number of items to select
     * @return array<string> Selected item keys
     */
    private function weightedRandomSelection(array $items, int $count): array
    {
        $selected = [];
        $availableItems = $items;

        for ($i = 0; $i < $count && !empty($availableItems); $i++) {
            // Calculate total weight
            $totalWeight = array_sum($availableItems);

            // Pick random number
            $random = random_int(1, $totalWeight);

            // Find which item was selected
            $currentWeight = 0;
            foreach ($availableItems as $item => $weight) {
                $currentWeight += $weight;
                if ($random <= $currentWeight) {
                    $selected[] = $item;
                    unset($availableItems[$item]); // Remove from available pool
                    break;
                }
            }
        }

        return $selected;
    }
}
