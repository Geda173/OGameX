<?php

namespace OGame\Services;

use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use OGame\GameMissions\BattleEngine\Models\BattleResult;
use OGame\GameObjects\Models\Enums\GameObjectType;
use OGame\GameObjects\Models\Units\UnitCollection;
use OGame\Models\BattleReport;
use OGame\Models\Enums\PlanetType;
use OGame\Models\RepairQueue;
use OGame\Models\Resources;

/**
 * Class SpaceDockService.
 *
 * Service for managing Space Dock ship repair functionality.
 *
 * @package OGame\Services
 */
class SpaceDockService
{
    /**
     * Calculate wreckage from a battle based on Space Dock mechanics.
     *
     * Now stores RAW defender ship losses (not filtered by Space Dock level).
     * Recovery percentage is applied dynamically based on current Space Dock level
     * when accessing wreckage, allowing retroactive benefits from building/upgrading.
     *
     * Returns an array of ship machine names and amounts (raw losses),
     * or an empty array if no wreckage is available (conditions not met).
     *
     * @param PlanetService $defenderPlanet
     * @param BattleResult $battleResult
     * @return array<string, int> Array of ship machine_name => raw amount lost
     */
    public function calculateWreckage(PlanetService $defenderPlanet, BattleResult $battleResult): array
    {
        // Calculate total ship value destroyed (in resource points)
        $totalShipValue = $battleResult->attackerResourceLoss->sum() +
                         $battleResult->defenderResourceLoss->sum();

        // Wreckage only appears if more than 150,000 resource points destroyed
        if ($totalShipValue <= 150000) {
            return [];
        }

        // Check if defender's ships participated with at least 5% of ship value
        $defenderShipValue = $battleResult->defenderResourceLoss->sum();
        if ($totalShipValue > 0 && ($defenderShipValue / $totalShipValue) < 0.05) {
            return [];
        }

        // Store RAW defender ship losses (100% of ships lost)
        // Recovery percentage will be applied later based on current Space Dock level
        $wreckage = [];

        // Process defender's lost ships (only ships, not defenses)
        foreach ($battleResult->defenderUnitsLost->units as $unit) {
            $unitObject = ObjectService::getUnitObjectByMachineName($unit->unitObject->machine_name);

            // Only process ships (not defenses)
            if ($unitObject->type !== GameObjectType::Ship) {
                continue;
            }

            // Store full amount (raw losses)
            if ($unit->amount > 0) {
                $wreckage[$unit->unitObject->machine_name] = $unit->amount;
            }
        }

        return $wreckage;
    }

    /**
     * Calculate recoverable wreckage from raw wreckage based on current Space Dock level.
     * This applies the recovery percentage to raw defender losses.
     *
     * @param array<string, int> $rawWreckage Raw defender ship losses
     * @param int $spaceDockLevel Current Space Dock level
     * @param int $debrisFieldPercentage Debris field setting
     * @return array<string, int> Recoverable amounts
     */
    public function calculateRecoverableFromRaw(array $rawWreckage, int $spaceDockLevel, int $debrisFieldPercentage): array
    {
        // Calculate recovery percentage based on current Space Dock level
        $recoveryPercentage = $this->calculateRecoveryPercentage($spaceDockLevel, $debrisFieldPercentage);

        // If no space dock or 0% recovery, nothing can be recovered
        if ($recoveryPercentage <= 0) {
            return [];
        }

        // Apply recovery percentage to raw wreckage
        $recoverable = [];
        foreach ($rawWreckage as $shipMachineName => $rawAmount) {
            $recoverableAmount = (int)floor($rawAmount * ($recoveryPercentage / 100));
            if ($recoverableAmount > 0) {
                $recoverable[$shipMachineName] = $recoverableAmount;
            }
        }

        return $recoverable;
    }

    /**
     * Calculate the recovery percentage based on Space Dock level.
     *
     * Recovery percentage table (levels 1-15):
     * - Level 1: 22.50%
     * - Level 2: 24.00%
     * - Level 3: 24.50%
     * - Level 4: 25.00%
     * - Level 5: 25.50%
     * - Level 6: 26.00%
     * - Level 7: 26.50%
     * - Level 8: 26.50%
     * - Level 9: 27.00%
     * - Level 10: 27.00%
     * - Level 11: 27.50%
     * - Level 12: 27.50%
     * - Level 13: 27.50%
     * - Level 14: 28.00%
     * - Level 15+: 28.00%
     *
     * @param int $spaceDockLevel
     * @param int $debrisFieldPercentage (unused, kept for compatibility)
     * @return float
     */
    private function calculateRecoveryPercentage(int $spaceDockLevel, int $debrisFieldPercentage): float
    {
        if ($spaceDockLevel <= 0) {
            return 0;
        }

        // Simple level-based recovery table
        $recoveryTable = [
            1 => 22.50,
            2 => 24.00,
            3 => 24.50,
            4 => 25.00,
            5 => 25.50,
            6 => 26.00,
            7 => 26.50,
            8 => 26.50,
            9 => 27.00,
            10 => 27.00,
            11 => 27.50,
            12 => 27.50,
            13 => 27.50,
            14 => 28.00,
            15 => 28.00,
        ];

        // Cap at level 15 (higher levels use level 15 value)
        $effectiveLevel = min($spaceDockLevel, 15);

        return $recoveryTable[$effectiveLevel];
    }

    /**
     * Get all available wreckage for a planet from battle reports.
     *
     * Returns battle reports that:
     * - Have wreckage data
     * - Are less than 3 days old
     * - Haven't been fully repaired
     *
     * @param PlanetService $planet
     * @return Collection<int, BattleReport>
     */
    public function getAvailableWreckage(PlanetService $planet): Collection
    {
        $coordinates = $planet->getPlanetCoordinates();
        $threeDaysAgo = Carbon::now()->subDays(3);

        // Build planet types to query - include both planet and moon if they exist
        $planetTypes = [$planet->getPlanetType()->value];

        // If this is a planet with a moon, also include moon wreckage
        if ($planet->isPlanet() && $planet->hasMoon()) {
            $planetTypes[] = PlanetType::Moon->value;
        }

        // If this is a moon with a planet, also include planet wreckage
        if ($planet->isMoon() && $planet->hasPlanet()) {
            $planetTypes[] = PlanetType::Planet->value;
        }

        // Get battle reports for this planet and/or moon
        $reports = BattleReport::where([
            ['planet_galaxy', $coordinates->galaxy],
            ['planet_system', $coordinates->system],
            ['planet_position', $coordinates->position],
            ['wreckage_dismissed', 0], // Exclude dismissed wreckage
        ])
            ->whereIn('planet_type', $planetTypes)
            ->where('created_at', '>=', $threeDaysAgo)
            ->whereNotNull('wreckage')
            ->get();

        // Filter out reports where all wreckage has been repaired
        return $reports->filter(function ($report) {
            if (empty($report->wreckage) || !is_array($report->wreckage)) {
                return false;
            }

            // Check if there's any wreckage left
            $hasWreckage = false;
            foreach ($report->wreckage as $amount) {
                if ($amount > 0) {
                    $hasWreckage = true;
                    break;
                }
            }

            return $hasWreckage;
        });
    }

    /**
     * Dismiss wreckage (let it burn up instead of repairing).
     *
     * @param int $battleReportId
     * @throws Exception
     */
    public function dismissWreckage(int $battleReportId): void
    {
        $battleReport = BattleReport::find($battleReportId);
        if (!$battleReport) {
            throw new Exception('Battle report not found.');
        }

        // Check if there are any active repairs for this battle report
        $activeRepairs = RepairQueue::where([
            ['battle_report_id', $battleReportId],
            ['canceled', 0],
        ])->whereIn('claimed', [0]) // Unclaimed repairs
        ->count();

        if ($activeRepairs > 0) {
            throw new Exception('Cannot dismiss wreckage while repairs are in progress. Cancel repairs first.');
        }

        // Mark wreckage as dismissed
        $battleReport->wreckage_dismissed = 1;
        $battleReport->save();
    }

    /**
     * Start repairing ships from wreckage.
     *
     * @param PlanetService $planet
     * @param int $battleReportId
     * @param string $shipMachineName
     * @param int $amount
     * @throws Exception
     */
    public function startRepair(PlanetService $planet, int $battleReportId, string $shipMachineName, int $amount): void
    {
        // Verify battle report exists and has wreckage
        $battleReport = BattleReport::find($battleReportId);
        if (!$battleReport) {
            throw new Exception('Battle report not found.');
        }

        // Calculate available wreckage (recoverable - consumed)
        $rawAmount = $battleReport->wreckage[$shipMachineName] ?? 0;
        if ($rawAmount <= 0) {
            throw new Exception('No wreckage available for this ship type.');
        }

        // Get current Space Dock level and debris field settings
        $spaceDockLevel = $planet->getObjectLevel('space_dock');
        $debrisFieldPercentage = app(SettingsService::class)->debrisFieldFromShips();

        // Calculate recoverable amount
        $rawWreckage = [$shipMachineName => $rawAmount];
        $recoverableWreckage = $this->calculateRecoverableFromRaw($rawWreckage, $spaceDockLevel, $debrisFieldPercentage);
        $recoverableAmount = $recoverableWreckage[$shipMachineName] ?? 0;

        // Get consumed amount
        $consumed = $battleReport->wreckage_consumed ?? [];
        $consumedAmount = $consumed[$shipMachineName] ?? 0;

        // Calculate available
        $availableAmount = $recoverableAmount - $consumedAmount;

        if ($availableAmount < $amount) {
            throw new Exception('Not enough wreckage available for repair. Available: ' . $availableAmount);
        }

        // Verify wreckage is not too old (3 days)
        $threeDaysAgo = Carbon::now()->subDays(3);
        if ($battleReport->created_at < $threeDaysAgo) {
            throw new Exception('Wreckage has burned up (older than 3 days).');
        }

        // Get ship object
        $shipObject = ObjectService::getUnitObjectByMachineName($shipMachineName);
        if (!$shipObject || $shipObject->type !== GameObjectType::Ship) {
            throw new Exception('Invalid ship type.');
        }

        // Check for duplicate repairs (prevent same ship from same battle being repaired multiple times)
        $existingRepair = RepairQueue::where([
            ['planet_id', $planet->getPlanetId()],
            ['battle_report_id', $battleReportId],
            ['ship_object_id', $shipObject->id],
            ['canceled', 0],
        ])->whereIn('processed', [0, 1]) // Include both active and completed but unclaimed repairs
        ->whereIn('claimed', [0]) // Only unclaimed repairs
        ->first();

        if ($existingRepair) {
            throw new Exception('A repair for this ship from this battle is already in progress or awaiting pickup.');
        }

        // Space Dock repairs are FREE - only time is required (no resources needed)
        $totalRepairCost = new Resources(0, 0, 0, 0);

        // Calculate repair time
        $repairTime = $this->calculateRepairTime($planet, $shipMachineName, $amount);

        // Track consumed wreckage (wreckage field now stores raw losses, never changes)
        $consumed = $battleReport->wreckage_consumed ?? [];
        $consumed[$shipMachineName] = ($consumed[$shipMachineName] ?? 0) + $amount;

        // Use raw DB update to ensure the JSON is saved correctly
        // Laravel's Eloquent can have issues with JSON dirty detection
        \DB::table('battle_reports')
            ->where('id', $battleReport->id)
            ->update([
                'wreckage_consumed' => json_encode($consumed),
                'updated_at' => now(),
            ]);

        // Create repair queue entry
        $repairQueue = new RepairQueue();
        $repairQueue->planet_id = $planet->getPlanetId();
        $repairQueue->battle_report_id = $battleReportId;
        $repairQueue->ship_object_id = $shipObject->id;
        $repairQueue->ship_amount = $amount;
        $repairQueue->ship_amount_claimed = 0;
        $repairQueue->metal_cost = $totalRepairCost->metal->get();
        $repairQueue->crystal_cost = $totalRepairCost->crystal->get();
        $repairQueue->deuterium_cost = $totalRepairCost->deuterium->get();
        $repairQueue->time_duration = $repairTime;
        $repairQueue->time_start = Carbon::now()->timestamp;
        $repairQueue->time_end = $repairQueue->time_start + $repairTime;
        $repairQueue->processed = 0;
        $repairQueue->canceled = 0;
        $repairQueue->save();
    }

    /**
     * Start batch repair - combines all wreckage by ship type into single repairs.
     *
     * @param PlanetService $planet
     * @param array $wreckageByShip Array where keys are ship machine names and values contain total_amount and battle_reports
     * @param array $battleReportIds All battle report IDs involved
     * @throws Exception
     */
    public function startBatchRepair(PlanetService $planet, array $wreckageByShip, array $battleReportIds): void
    {
        // Verify Space Dock is built
        if ($planet->getObjectLevel('space_dock') < 1) {
            throw new Exception('Space Dock is not built on this planet.');
        }

        // Wrap entire operation in a database transaction to prevent race conditions
        \DB::transaction(function () use ($planet, $wreckageByShip, $battleReportIds) {
            // Process each ship type
            foreach ($wreckageByShip as $shipMachineName => $data) {
                $this->processBatchRepairForShipType($planet, $shipMachineName, $data, $battleReportIds);
            }
        });
    }

    /**
     * Process batch repair for a single ship type (internal method called from transaction).
     *
     * @param PlanetService $planet
     * @param string $shipMachineName
     * @param array $data
     * @param array $battleReportIds
     * @throws Exception
     */
    private function processBatchRepairForShipType(PlanetService $planet, string $shipMachineName, array $data, array $battleReportIds): void
    {
        $totalAmount = $data['total_amount'];
        $battleReports = $data['battle_reports'];

        // Get ship object
        $shipObject = ObjectService::getUnitObjectByMachineName($shipMachineName);
        if (!$shipObject || $shipObject->type !== GameObjectType::Ship) {
            return; // Skip invalid ship types
        }

        // Check if there's already a repair for this ship type in progress
        // Use lockForUpdate to prevent race conditions when multiple simultaneous requests
        // try to create/extend the same repair
        $existingRepair = RepairQueue::where([
            ['planet_id', $planet->getPlanetId()],
            ['ship_object_id', $shipObject->id],
            ['canceled', 0],
            ['claimed', 0], // Not fully claimed
        ])->lockForUpdate()->first();

        // Track consumed wreckage from all involved battle reports
        foreach ($battleReports as $battleData) {
            $battleReport = BattleReport::find($battleData['battle_report_id']);
            if (!$battleReport) {
                continue;
            }

            // Add to consumed wreckage (wreckage field now stores raw losses, never changes)
            $consumed = $battleReport->wreckage_consumed ?? [];
            $consumed[$shipMachineName] = ($consumed[$shipMachineName] ?? 0) + $battleData['amount'];

            // Use raw DB update to ensure the JSON is saved correctly
            // Laravel's Eloquent can have issues with JSON dirty detection
            \DB::table('battle_reports')
                ->where('id', $battleReport->id)
                ->update([
                    'wreckage_consumed' => json_encode($consumed),
                    'updated_at' => now(),
                ]);
        }

        if ($existingRepair) {
            // Extend existing repair by adding new ships
            $currentTime = Carbon::now()->timestamp;

            // Calculate repair time for the new ships being added
            $additionalRepairTime = $this->calculateRepairTime($planet, $shipMachineName, $totalAmount);

            // Extend the repair: add ships and extend timeline
            $existingRepair->ship_amount += $totalAmount;
            $existingRepair->time_duration += $additionalRepairTime;
            $existingRepair->time_end += $additionalRepairTime;

            $existingRepair->save();
        } else {
            // Create new repair queue entry for this ship type
            $repairTime = $this->calculateRepairTime($planet, $shipMachineName, $totalAmount);

            $repairQueue = new RepairQueue();
            $repairQueue->planet_id = $planet->getPlanetId();
            $repairQueue->battle_report_id = $battleReportIds[0]; // Use first battle report ID for reference
            $repairQueue->ship_object_id = $shipObject->id;
            $repairQueue->ship_amount = $totalAmount;
            $repairQueue->ship_amount_claimed = 0;
            $repairQueue->metal_cost = 0;
            $repairQueue->crystal_cost = 0;
            $repairQueue->deuterium_cost = 0;
            $repairQueue->time_duration = $repairTime;
            $repairQueue->time_start = Carbon::now()->timestamp;
            $repairQueue->time_end = $repairQueue->time_start + $repairTime;
            $repairQueue->processed = 0;
            $repairQueue->canceled = 0;
            $repairQueue->save();
        }
    }

    /**
     * Calculate repair time for ships.
     *
     * Based on wiki: max 12 hours, min 30 minutes.
     *
     * @param PlanetService $planet
     * @param string $shipMachineName
     * @param int $amount
     * @return int Time in seconds
     */
    public function calculateRepairTime(PlanetService $planet, string $shipMachineName, int $amount): int
    {
        // Get base construction time for one ship
        $baseTime = $planet->getUnitConstructionTime($shipMachineName);

        // Repair time is typically faster than construction time (e.g., 50% of construction time)
        $totalRepairTime = (int)($baseTime * $amount * 0.5);

        // Apply min/max limits
        $minTime = 30 * 60; // 30 minutes
        $maxTime = 12 * 60 * 60; // 12 hours

        return max($minTime, min($maxTime, $totalRepairTime));
    }

    /**
     * Retrieve all finished repair queue items for a planet.
     *
     * @param int $planet_id
     * @return Collection<int, RepairQueue>
     */
    public function retrieveFinished(int $planet_id): Collection
    {
        return RepairQueue::where([
            ['planet_id', $planet_id],
            ['time_end', '<=', Carbon::now()->timestamp],
            ['processed', 0],
            ['canceled', 0],
        ])
            ->orderBy('time_start', 'asc')
            ->get();
    }

    /**
     * Retrieve all active repair queue items for a planet.
     *
     * @param PlanetService $planet
     * @return Collection<int, RepairQueue>
     */
    public function retrieveQueueItems(PlanetService $planet): Collection
    {
        return RepairQueue::where([
            ['planet_id', $planet->getPlanetId()],
            ['processed', 0],
            ['canceled', 0],
        ])
            ->orderBy('time_start', 'asc')
            ->get();
    }

    /**
     * Process completed repairs (mark as ready for pickup, do NOT auto-add ships).
     *
     * This should be called periodically by the game loop.
     *
     * @param PlanetService $planet
     */
    public function processRepairs(PlanetService $planet): void
    {
        $finishedRepairs = $this->retrieveFinished($planet->getPlanetId());

        foreach ($finishedRepairs as $repair) {
            // Mark as processed (ready for pickup)
            // Ships are NOT automatically added - player must manually claim them
            $repair->processed = 1;
            $repair->save();
        }

        // Also auto-claim repairs that have been waiting for 72+ hours
        $this->autoClaimExpiredRepairs($planet);
    }

    /**
     * Auto-claim repairs that completed more than 72 hours ago.
     *
     * Per OGame rules: "If this is not done, individual ships of any type
     * will be returned to service after 3 days."
     *
     * This should be called periodically by the game loop.
     *
     * @param PlanetService $planet
     */
    public function autoClaimExpiredRepairs(PlanetService $planet): void
    {
        $threeDaysAgo = Carbon::now()->subDays(3)->timestamp;

        // Find repairs that finished 72+ hours ago and haven't been fully claimed
        $expiredRepairs = RepairQueue::where([
            ['planet_id', $planet->getPlanetId()],
            ['canceled', 0],
            ['claimed', 0], // Not fully claimed
        ])
        ->where('time_end', '<=', $threeDaysAgo)
        ->get();

        foreach ($expiredRepairs as $repair) {
            // Calculate unclaimed ships
            $unclaimedShips = $repair->ship_amount - ($repair->ship_amount_claimed ?? 0);

            if ($unclaimedShips <= 0) {
                continue;
            }

            // Get ship object
            $shipObject = ObjectService::getUnitObjectById($repair->ship_object_id);

            // Create a UnitCollection with the unclaimed ships
            $autoClaimedUnits = new UnitCollection();
            $autoClaimedUnits->addUnit($shipObject, $unclaimedShips);

            // Add ships back to planet automatically
            $planet->addUnits($autoClaimedUnits, false);

            // Mark as fully claimed
            $repair->ship_amount_claimed = $repair->ship_amount;
            $repair->claimed = 1;
            $repair->processed = 1;
            $repair->save();
        }

        // Save planet if any ships were added
        if (!$expiredRepairs->isEmpty()) {
            $planet->save();
        }
    }

    /**
     * Retrieve all completed but unclaimed repairs for a planet.
     *
     * @param PlanetService $planet
     * @return Collection<int, RepairQueue>
     */
    public function retrieveReadyForPickup(PlanetService $planet): Collection
    {
        return RepairQueue::where([
            ['planet_id', $planet->getPlanetId()],
            ['processed', 1],
            ['claimed', 0],
            ['canceled', 0],
        ])
            ->orderBy('time_end', 'asc')
            ->get();
    }

    /**
     * Calculate how many ships have been completed in a repair based on elapsed time.
     *
     * @param RepairQueue $repair
     * @return int Number of ships completed (but not yet claimed)
     */
    public function calculateCompletedShips(RepairQueue $repair): int
    {
        $currentTime = Carbon::now()->timestamp;
        $elapsedTime = $currentTime - $repair->time_start;
        $totalTime = $repair->time_duration;

        // If repair hasn't started yet, no ships completed
        if ($elapsedTime <= 0) {
            return 0;
        }

        // If repair is fully complete, all ships are done
        if ($elapsedTime >= $totalTime) {
            return $repair->ship_amount;
        }

        // Calculate partial completion (OGame-style)
        // Ships become available incrementally as they finish
        $shipsCompleted = (int)floor(($elapsedTime / $totalTime) * $repair->ship_amount);

        return $shipsCompleted;
    }

    /**
     * Get the number of ships available to claim from a repair.
     *
     * @param RepairQueue $repair
     * @return int Number of ships ready to claim
     */
    public function getAvailableShipsToClaim(RepairQueue $repair): int
    {
        $completedShips = $this->calculateCompletedShips($repair);
        $alreadyClaimed = $repair->ship_amount_claimed ?? 0;

        return max(0, $completedShips - $alreadyClaimed);
    }

    /**
     * Claim repaired ships and add them to the planet.
     * Supports partial collection - claim any amount up to what's been completed.
     *
     * @param PlanetService $planet
     * @param int|null $repairQueueId Specific repair to claim, or null to claim all ready repairs
     * @param int|null $amount Number of ships to claim (null = claim all available)
     * @throws Exception
     */
    public function claimRepairs(PlanetService $planet, ?int $repairQueueId = null, ?int $amount = null): void
    {
        if ($repairQueueId !== null) {
            // Claim specific repair (partial or full)
            $repair = RepairQueue::where([
                ['id', $repairQueueId],
                ['planet_id', $planet->getPlanetId()],
                ['canceled', 0],
            ])->first();

            if (!$repair) {
                throw new Exception('Repair not found.');
            }

            // Calculate how many ships are available to claim
            $availableShips = $this->getAvailableShipsToClaim($repair);

            if ($availableShips <= 0) {
                throw new Exception('No ships are ready to claim yet.');
            }

            // Determine how many to claim
            $claimAmount = $amount ?? $availableShips;

            if ($claimAmount > $availableShips) {
                throw new Exception('Cannot claim more ships than are ready. Only ' . $availableShips . ' ships are available.');
            }

            if ($claimAmount <= 0) {
                throw new Exception('Invalid claim amount.');
            }

            // Get ship object
            $shipObject = ObjectService::getUnitObjectById($repair->ship_object_id);

            // Create a UnitCollection with the claimed ships
            $claimedUnits = new UnitCollection();
            $claimedUnits->addUnit($shipObject, $claimAmount);

            // Add ships back to planet
            $planet->addUnits($claimedUnits, false);

            // Update claimed amount
            $repair->ship_amount_claimed = ($repair->ship_amount_claimed ?? 0) + $claimAmount;

            // If all ships have been claimed, mark as fully claimed
            if ($repair->ship_amount_claimed >= $repair->ship_amount) {
                $repair->claimed = 1;
                $repair->processed = 1;
            }

            $repair->save();
            $planet->save();
        } else {
            // Claim all ready repairs (all available ships from all completed repairs)
            $repairs = RepairQueue::where([
                ['planet_id', $planet->getPlanetId()],
                ['canceled', 0],
            ])->get();

            foreach ($repairs as $repair) {
                $availableShips = $this->getAvailableShipsToClaim($repair);

                if ($availableShips <= 0) {
                    continue;
                }

                // Get ship object
                $shipObject = ObjectService::getUnitObjectById($repair->ship_object_id);

                // Create a UnitCollection with the available ships
                $claimedUnits = new UnitCollection();
                $claimedUnits->addUnit($shipObject, $availableShips);

                // Add ships back to planet
                $planet->addUnits($claimedUnits, false);

                // Update claimed amount
                $repair->ship_amount_claimed = ($repair->ship_amount_claimed ?? 0) + $availableShips;

                // If all ships have been claimed, mark as fully claimed
                if ($repair->ship_amount_claimed >= $repair->ship_amount) {
                    $repair->claimed = 1;
                    $repair->processed = 1;
                }

                $repair->save();
            }

            // Save planet once after all ships are added
            $planet->save();
        }
    }

    /**
     * Cancel a repair queue entry.
     * Only restores unclaimed ships to wreckage.
     *
     * @param PlanetService $planet
     * @param int $repairQueueId
     * @throws Exception
     */
    public function cancelRepair(PlanetService $planet, int $repairQueueId): void
    {
        $repair = RepairQueue::where([
            ['id', $repairQueueId],
            ['planet_id', $planet->getPlanetId()],
            ['canceled', 0],
        ])->first();

        if (!$repair) {
            throw new Exception('Repair queue entry not found.');
        }

        // Cannot cancel fully completed and claimed repairs
        if ($repair->claimed == 1) {
            throw new Exception('Cannot cancel a repair that has been fully claimed.');
        }

        // Calculate how many ships to restore (total - claimed)
        $unclaimedShips = $repair->ship_amount - ($repair->ship_amount_claimed ?? 0);

        // Refund resources (proportional to unclaimed ships)
        if ($unclaimedShips > 0) {
            $refund = new Resources(
                (int)(($repair->metal_cost / $repair->ship_amount) * $unclaimedShips),
                (int)(($repair->crystal_cost / $repair->ship_amount) * $unclaimedShips),
                (int)(($repair->deuterium_cost / $repair->ship_amount) * $unclaimedShips),
                0
            );
            $planet->addResources($refund);

            // Restore unclaimed wreckage by subtracting from consumed
            $battleReport = BattleReport::find($repair->battle_report_id);
            if ($battleReport) {
                $shipObject = ObjectService::getUnitObjectById($repair->ship_object_id);
                $consumed = $battleReport->wreckage_consumed ?? [];
                $consumed[$shipObject->machine_name] = max(0, ($consumed[$shipObject->machine_name] ?? 0) - $unclaimedShips);

                // Remove from consumed array if 0
                if ($consumed[$shipObject->machine_name] <= 0) {
                    unset($consumed[$shipObject->machine_name]);
                }

                // Use raw DB update to ensure the JSON is saved correctly
                \DB::table('battle_reports')
                    ->where('id', $battleReport->id)
                    ->update([
                        'wreckage_consumed' => json_encode($consumed),
                        'updated_at' => now(),
                    ]);
            }
        }

        // Mark as canceled
        $repair->canceled = 1;
        $repair->save();
    }
}
