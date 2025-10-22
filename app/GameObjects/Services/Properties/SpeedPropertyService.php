<?php

namespace OGame\GameObjects\Services\Properties;

use OGame\GameObjects\Models\Fields\GameObjectSpeedUpgrade;
use OGame\GameObjects\Models\Fields\GameObjectPropertyDetails;
use OGame\GameObjects\Services\Properties\Abstracts\ObjectPropertyService;
use OGame\Services\PlayerService;

/**
 * Speed calculation service.
 *
 * Keeps the original research-scaling behavior (combustion 10%/lvl, impulse 20%/lvl,
 * hyperspace 30%/lvl) and adds an optional per-ship base-speed bump when certain
 * tech thresholds are reached. Currently enabled for Small Cargo (Impulse ≥ 5).
 */
class SpeedPropertyService extends ObjectPropertyService
{
    protected string $propertyName = 'speed';

    /**
     * Calculate the total value of the Speed property with optional base-speed upgrades.
     *
     * We override the parent to allow a higher *base* speed (not just a different scaling)
     * once a player meets a drive-upgrade threshold. This is per-player and does not affect others.
     */
    public function calculateProperty(PlayerService $player): GameObjectPropertyDetails
    {
        $base = $this->base_value;
        $object = $this->parent_object;

        // --- Per-ship base speed upgrades ---
        // Small Cargo: when player has Impulse Drive ≥ 5, bump base from 5,000 -> 10,000.
        if ($object->machine_name === 'small_cargo') {
            $impulseLvl = $player->getResearchLevel('impulse_drive');
            if ($impulseLvl >= 5) {
                // Only bump if current base is still the pre-upgrade value (safety).
                if ($base < 10000) {
                    $base = 10000;
                }
            }
        }

        // (If you want similar behavior for other ships later, add cases here; e.g. recycler/bomber thresholds.)

        // Apply the normal research bonus on top of the (possibly upgraded) base.
        $bonusPercentage = $this->getBonusPercentage($player);
        $bonusValue = ($base / 100) * $bonusPercentage;
        $totalValue = $base + $bonusValue;

        // Build a breakdown (purely informational).
        $breakdown = [
            'rawValue'   => $base,
            'bonuses'    => [
                [
                    'type' => 'Research bonus',
                    'value' => $bonusValue,
                    'percentage' => $bonusPercentage,
                ],
            ],
            'totalValue' => $totalValue,
        ];

        // Annotate that a base upgrade happened (visible only in debug tooling).
        if ($base !== $this->base_value) {
            $breakdown['baseUpgradeNote'] = sprintf(
                'Base speed upgraded from %d to %d due to drive threshold.',
                $this->base_value,
                $base
            );
        }

        return new GameObjectPropertyDetails($base, $bonusValue, $totalValue, $breakdown);
    }

    /**
     * Determine research bonus percentage (unchanged from original behavior).
     *
     * @throws \Exception
     */
    protected function getBonusPercentage(PlayerService $player): int
    {
        // Speed bonus is calculated based on drive technology:
        // Combustion: +10%/lvl, Impulse: +20%/lvl, Hyperspace: +30%/lvl
        // "speed_upgrade" on an object can switch which drive applies after a threshold.

        $object = $this->parent_object;

        $combustion = $player->getResearchLevel('combustion_drive');
        $impulse    = $player->getResearchLevel('impulse_drive');
        $hyperspace = $player->getResearchLevel('hyperspace_drive');

        $bonusPerLevel = 0;
        $appliesLevel  = 0;

        // If the object defines speed upgrades, they take precedence (last matching wins).
        if (!empty($object->properties->speed_upgrade)) {
            /** @var GameObjectSpeedUpgrade $upgrade */
            foreach ($object->properties->speed_upgrade as $upgrade) {
                if ($upgrade->object_machine_name === 'combustion_drive' && $combustion >= $upgrade->level) {
                    $bonusPerLevel = 10;
                    $appliesLevel  = $combustion;
                } elseif ($upgrade->object_machine_name === 'impulse_drive' && $impulse >= $upgrade->level) {
                    $bonusPerLevel = 20;
                    $appliesLevel  = $impulse;
                } elseif ($upgrade->object_machine_name === 'hyperspace_drive' && $hyperspace >= $upgrade->level) {
                    $bonusPerLevel = 30;
                    $appliesLevel  = $hyperspace;
                }
            }
        }

        // Fallback to the ship's required drive if no upgrade threshold has been met.
        if ($bonusPerLevel === 0) {
            foreach ($object->requirements as $req) {
                if ($req->object_machine_name === 'combustion_drive') {
                    $bonusPerLevel = 10;
                    $appliesLevel  = $combustion;
                } elseif ($req->object_machine_name === 'impulse_drive') {
                    $bonusPerLevel = 20;
                    $appliesLevel  = $impulse;
                } elseif ($req->object_machine_name === 'hyperspace_drive') {
                    $bonusPerLevel = 30;
                    $appliesLevel  = $hyperspace;
                }
            }
        }

        return $bonusPerLevel * $appliesLevel;
    }
}
