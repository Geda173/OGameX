<?php

namespace OGame\Services;

use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use OGame\GameMissions\BattleEngine\Models\BattleResult;
use OGame\GameObjects\Models\Units\UnitCollection;
use OGame\Models\BattleReport;
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
     * Returns an array of ship machine names and amounts that can be repaired,
     * or an empty array if no wreckage is available (conditions not met).
     *
     * @param PlanetService $defenderPlanet
     * @param BattleResult $battleResult
     * @return array<string, int> Array of ship machine_name => amount
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

        // Get debris field percentage from game settings
        $debrisFieldPercentage = app(SettingsService::class)->debrisFieldFromShips();

        // Get Space Dock level for the defender's planet (or moon if applicable)
        $spaceDockLevel = $defenderPlanet->getObjectLevel('space_dock');

        // Calculate recovery percentage based on Space Dock level and debris field settings
        $recoveryPercentage = $this->calculateRecoveryPercentage($spaceDockLevel, $debrisFieldPercentage);

        // If no space dock or 0% recovery, no wreckage available
        if ($recoveryPercentage <= 0) {
            return [];
        }

        // Calculate wreckage for each ship type
        $wreckage = [];

        // Process defender's lost ships (only ships, not defenses)
        foreach ($battleResult->defenderUnitsLost->units as $unit) {
            $unitObject = ObjectService::getUnitObjectByMachineName($unit->unitObject->machine_name);

            // Only process ships (not defenses)
            if ($unitObject->unittype !== 'ships') {
                continue;
            }

            $recoverableAmount = (int)floor($unit->amount * ($recoveryPercentage / 100));
            if ($recoverableAmount > 0) {
                $wreckage[$unit->unitObject->machine_name] = $recoverableAmount;
            }
        }

        return $wreckage;
    }

    /**
     * Calculate the recovery percentage based on Space Dock level and debris field settings.
     *
     * Based on the OGame wiki table:
     * - Level 1: 31.50% (30% DF), 27.00% (40% DF), 22.50% (50% DF), 18.00% (60% DF), 13.50% (70% DF)
     * - Level 2: 33.60% (30% DF), 28.80% (40% DF), 24.00% (50% DF), 19.20% (60% DF), 14.40% (70% DF)
     * - Level 3: 34.30% (30% DF), 29.40% (40% DF), 24.50% (50% DF), 19.60% (60% DF), 14.70% (70% DF)
     * - Level 4: 35.00% (30% DF), 30.00% (40% DF), 25.00% (50% DF), 20.00% (60% DF), 15.00% (70% DF)
     *
     * @param int $spaceDockLevel
     * @param int $debrisFieldPercentage
     * @return float
     */
    private function calculateRecoveryPercentage(int $spaceDockLevel, int $debrisFieldPercentage): float
    {
        if ($spaceDockLevel <= 0) {
            return 0;
        }

        // Recovery percentage table based on wiki data
        $recoveryTable = [
            1 => [
                30 => 31.50,
                40 => 27.00,
                50 => 22.50,
                60 => 18.00,
                70 => 13.50,
            ],
            2 => [
                30 => 33.60,
                40 => 28.80,
                50 => 24.00,
                60 => 19.20,
                70 => 14.40,
            ],
            3 => [
                30 => 34.30,
                40 => 29.40,
                50 => 24.50,
                60 => 19.60,
                70 => 14.70,
            ],
            4 => [
                30 => 35.00,
                40 => 30.00,
                50 => 25.00,
                60 => 20.00,
                70 => 15.00,
            ],
        ];

        // Cap space dock level at 4 (higher levels use level 4 values)
        $effectiveLevel = min($spaceDockLevel, 4);

        // If debris field percentage is not in the table, interpolate or use closest value
        if (!isset($recoveryTable[$effectiveLevel][$debrisFieldPercentage])) {
            // Find the two closest debris field percentages
            $dfKeys = array_keys($recoveryTable[$effectiveLevel]);
            sort($dfKeys);

            // If below minimum, use minimum
            if ($debrisFieldPercentage < $dfKeys[0]) {
                return $recoveryTable[$effectiveLevel][$dfKeys[0]];
            }

            // If above maximum, use maximum
            if ($debrisFieldPercentage > end($dfKeys)) {
                return $recoveryTable[$effectiveLevel][end($dfKeys)];
            }

            // Interpolate between two closest values
            $lowerKey = 0;
            $upperKey = 0;
            foreach ($dfKeys as $key) {
                if ($key <= $debrisFieldPercentage) {
                    $lowerKey = $key;
                }
                if ($key >= $debrisFieldPercentage && $upperKey === 0) {
                    $upperKey = $key;
                }
            }

            if ($lowerKey === $upperKey) {
                return $recoveryTable[$effectiveLevel][$lowerKey];
            }

            $lowerValue = $recoveryTable[$effectiveLevel][$lowerKey];
            $upperValue = $recoveryTable[$effectiveLevel][$upperKey];

            // Linear interpolation
            $ratio = ($debrisFieldPercentage - $lowerKey) / ($upperKey - $lowerKey);
            return $lowerValue + ($upperValue - $lowerValue) * $ratio;
        }

        return $recoveryTable[$effectiveLevel][$debrisFieldPercentage];
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

        // Get battle reports for this planet
        $reports = BattleReport::where([
            ['planet_galaxy', $coordinates->galaxy],
            ['planet_system', $coordinates->system],
            ['planet_position', $coordinates->position],
            ['planet_type', $planet->getPlanetType()->value],
            ['wreckage_dismissed', 0], // Exclude dismissed wreckage
        ])
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

        // Verify wreckage is available for this ship type
        if (empty($battleReport->wreckage[$shipMachineName]) || $battleReport->wreckage[$shipMachineName] < $amount) {
            throw new Exception('Not enough wreckage available for repair.');
        }

        // Verify wreckage is not too old (3 days)
        $threeDaysAgo = Carbon::now()->subDays(3);
        if ($battleReport->created_at < $threeDaysAgo) {
            throw new Exception('Wreckage has burned up (older than 3 days).');
        }

        // Get ship object
        $shipObject = ObjectService::getUnitObjectByMachineName($shipMachineName);
        if (!$shipObject || $shipObject->unittype !== 'ships') {
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

        // Calculate repair cost (percentage of original ship cost)
        $repairCostPercentage = 0.5; // 50% of original cost
        $shipPrice = ObjectService::getUnitObjectPrice($shipMachineName, $planet);
        $totalRepairCost = new Resources(
            (int)($shipPrice->metal->get() * $amount * $repairCostPercentage),
            (int)($shipPrice->crystal->get() * $amount * $repairCostPercentage),
            (int)($shipPrice->deuterium->get() * $amount * $repairCostPercentage),
            0
        );

        // Verify planet has enough resources
        if (!$planet->hasResources($totalRepairCost)) {
            throw new Exception('Not enough resources for repair.');
        }

        // Calculate repair time
        $repairTime = $this->calculateRepairTime($planet, $shipMachineName, $amount);

        // Deduct resources from planet
        $planet->deductResources($totalRepairCost);

        // Deduct wreckage from battle report
        $wreckage = $battleReport->wreckage;
        $wreckage[$shipMachineName] -= $amount;
        $battleReport->wreckage = $wreckage;
        $battleReport->save();

        // Create repair queue entry
        $repairQueue = new RepairQueue();
        $repairQueue->planet_id = $planet->getPlanetId();
        $repairQueue->battle_report_id = $battleReportId;
        $repairQueue->ship_object_id = $shipObject->id;
        $repairQueue->ship_amount = $amount;
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
     * Calculate repair time for ships.
     *
     * Based on wiki: max 12 hours, min 30 minutes.
     *
     * @param PlanetService $planet
     * @param string $shipMachineName
     * @param int $amount
     * @return int Time in seconds
     */
    private function calculateRepairTime(PlanetService $planet, string $shipMachineName, int $amount): int
    {
        // Get base construction time for one ship
        $baseTime = $planet->getShipyardConstructionTime($shipMachineName);

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
     * Claim repaired ships and add them to the planet.
     *
     * @param PlanetService $planet
     * @param int|null $repairQueueId Specific repair to claim, or null to claim all ready repairs
     * @throws Exception
     */
    public function claimRepairs(PlanetService $planet, ?int $repairQueueId = null): void
    {
        if ($repairQueueId !== null) {
            // Claim specific repair
            $repairs = RepairQueue::where([
                ['id', $repairQueueId],
                ['planet_id', $planet->getPlanetId()],
                ['processed', 1],
                ['claimed', 0],
                ['canceled', 0],
            ])->get();

            if ($repairs->isEmpty()) {
                throw new Exception('Repair not found or not ready for pickup.');
            }
        } else {
            // Claim all ready repairs
            $repairs = $this->retrieveReadyForPickup($planet);
        }

        foreach ($repairs as $repair) {
            // Get ship object
            $shipObject = ObjectService::getUnitObjectById($repair->ship_object_id);

            // Create a UnitCollection with the repaired ships
            $repairedUnits = new UnitCollection();
            $repairedUnits->addUnit($shipObject, $repair->ship_amount);

            // Add ships back to planet
            $planet->addUnits($repairedUnits, false);

            // Mark as claimed
            $repair->claimed = 1;
            $repair->save();
        }

        // Save planet once after all ships are added
        $planet->save();
    }

    /**
     * Cancel a repair queue entry.
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
            ['processed', 0],
            ['canceled', 0],
        ])->first();

        if (!$repair) {
            throw new Exception('Repair queue entry not found.');
        }

        // Refund resources
        $refund = new Resources(
            $repair->metal_cost,
            $repair->crystal_cost,
            $repair->deuterium_cost,
            0
        );
        $planet->addResources($refund);

        // Restore wreckage to battle report
        $battleReport = BattleReport::find($repair->battle_report_id);
        if ($battleReport) {
            $shipObject = ObjectService::getUnitObjectById($repair->ship_object_id);
            $wreckage = $battleReport->wreckage ?? [];
            $wreckage[$shipObject->machine_name] = ($wreckage[$shipObject->machine_name] ?? 0) + $repair->ship_amount;
            $battleReport->wreckage = $wreckage;
            $battleReport->save();
        }

        // Mark as canceled
        $repair->canceled = 1;
        $repair->save();
    }
}
