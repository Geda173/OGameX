<?php

namespace OGame\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use OGame\Services\ObjectService;
use OGame\Services\PlayerService;
use OGame\Services\SpaceDockService;

class SpaceDockController extends OGameController
{
    /**
     * Shows the space dock as an overlay (AJAX request)
     *
     * @param Request $request
     * @param PlayerService $player
     * @param SpaceDockService $spaceDockService
     * @return View
     * @throws Exception
     */
    public function overlay(Request $request, PlayerService $player, SpaceDockService $spaceDockService): View
    {
        $planet = $player->planets->current();

        // Verify space dock is built
        $spaceDockLevel = $planet->getObjectLevel('space_dock');
        if ($spaceDockLevel < 1) {
            return view('ingame.spacedock.error')->with([
                'error_message' => 'Space Dock is not built on this planet.',
            ]);
        }

        // Get available wreckage from battle reports
        $availableWreckage = $spaceDockService->getAvailableWreckage($planet);

        // Get current repair queue (in progress)
        $repairQueue = $spaceDockService->retrieveQueueItems($planet);

        // Get ready for pickup repairs
        $readyForPickup = $spaceDockService->retrieveReadyForPickup($planet);

        // Get debris field percentage for recovery calculation
        $debrisFieldPercentage = app(\OGame\Services\SettingsService::class)->debrisFieldFromShips();

        // Prepare wreckage data for display
        // Now we calculate recoverable amounts based on CURRENT Space Dock level
        $wreckageData = [];
        foreach ($availableWreckage as $report) {
            if (empty($report->wreckage)) {
                continue;
            }

            // Calculate recoverable amount based on CURRENT Space Dock level
            $rawWreckage = $report->wreckage; // Now stores raw defender losses
            $recoverableWreckage = $spaceDockService->calculateRecoverableFromRaw(
                $rawWreckage,
                $spaceDockLevel,
                $debrisFieldPercentage
            );

            // Get consumed amounts (ships already taken for repairs)
            $consumed = $report->wreckage_consumed ?? [];

            $tempShips = []; // Temporary array to collect ships for this battle

            foreach ($recoverableWreckage as $machineName => $recoverableAmount) {
                if ($recoverableAmount <= 0) {
                    continue;
                }

                try {
                    $shipObject = ObjectService::getUnitObjectByMachineName($machineName);

                    // Subtract consumed amount
                    $consumedAmount = $consumed[$machineName] ?? 0;
                    $availableAmount = $recoverableAmount - $consumedAmount;

                    // Skip if no wreckage available after subtracting consumed
                    if ($availableAmount <= 0) {
                        continue;
                    }

                    $tempShips[] = [
                        'machine_name' => $machineName,
                        'name' => $shipObject->title,
                        'amount' => $availableAmount,
                    ];
                } catch (Exception $e) {
                    // Skip invalid ship types
                    continue;
                }
            }

            // Only add this battle report if it has available ships
            if (!empty($tempShips)) {
                $wreckageData[$report->id] = [
                    'battle_report_id' => $report->id,
                    'created_at' => $report->created_at,
                    'ships' => $tempShips,
                ];
            }
        }

        // Prepare repair queue data (includes partial completion info)
        $queueData = [];
        $shipTypesInRepair = []; // Track ship types currently being repaired
        $battleReportsWithRepairs = []; // Track which battle reports have active repairs
        foreach ($repairQueue as $repair) {
            $shipObject = ObjectService::getUnitObjectById($repair->ship_object_id);
            $shipTypesInRepair[] = $shipObject->machine_name; // Add to list

            // Track battle reports with active repairs (for dismiss button logic)
            if (!in_array($repair->battle_report_id, $battleReportsWithRepairs)) {
                $battleReportsWithRepairs[] = $repair->battle_report_id;
            }

            // Calculate partial completion progress
            $completedShips = $spaceDockService->calculateCompletedShips($repair);
            $availableShips = $spaceDockService->getAvailableShipsToClaim($repair);
            $claimedShips = $repair->ship_amount_claimed ?? 0;

            $queueData[] = [
                'id' => $repair->id,
                'ship_name' => $shipObject->title,
                'ship_amount' => $repair->ship_amount,
                'ship_amount_claimed' => $claimedShips,
                'ships_completed' => $completedShips,
                'ships_available' => $availableShips,
                'time_end' => $repair->time_end,
                'time_remaining' => max(0, $repair->time_end - time()),
                'time_start' => $repair->time_start,
                'time_duration' => $repair->time_duration,
            ];
        }

        // Prepare ready for pickup data (legacy - now handled by partial collection)
        $pickupData = [];
        foreach ($readyForPickup as $repair) {
            $shipObject = ObjectService::getUnitObjectById($repair->ship_object_id);
            $pickupData[] = [
                'id' => $repair->id,
                'ship_name' => $shipObject->title,
                'ship_amount' => $repair->ship_amount,
            ];
        }

        // Calculate total repairable ships and total repair time
        $totalRepairableShips = 0;
        $totalRepairTime = 0;
        foreach ($wreckageData as $wreckage) {
            foreach ($wreckage['ships'] as $ship) {
                $totalRepairableShips += $ship['amount'];
                // Calculate repair time for this ship type and amount
                $repairTime = $spaceDockService->calculateRepairTime($planet, $ship['machine_name'], $ship['amount']);
                // Track the maximum repair time (repairs run in parallel per the wiki)
                $totalRepairTime = max($totalRepairTime, $repairTime);
            }
        }

        return view('ingame.spacedock.overlay')->with([
            'planet' => $planet,
            'space_dock_level' => $spaceDockLevel,
            'wreckage_data' => $wreckageData,
            'queue_data' => $queueData,
            'pickup_data' => $pickupData,
            'total_repairable_ships' => $totalRepairableShips,
            'total_repair_time' => $totalRepairTime,
            'ship_types_in_repair' => $shipTypesInRepair,
            'battle_reports_with_repairs' => $battleReportsWithRepairs,
        ]);
    }

    /**
     * Shows the space dock index page
     *
     * @param Request $request
     * @param PlayerService $player
     * @param SpaceDockService $spaceDockService
     * @return View
     * @throws Exception
     */
    public function index(Request $request, PlayerService $player, SpaceDockService $spaceDockService): View
    {
        $this->setBodyId('repairDock');
        $planet = $player->planets->current();

        // Get available wreckage from battle reports
        $availableWreckage = $spaceDockService->getAvailableWreckage($planet);

        // Get current repair queue (in progress)
        $repairQueue = $spaceDockService->retrieveQueueItems($planet);

        // Get ready for pickup repairs
        $readyForPickup = $spaceDockService->retrieveReadyForPickup($planet);

        // Get Space Dock level
        $spaceDockLevel = $planet->getObjectLevel('space_dock');

        // Prepare wreckage data for view
        $wreckageData = [];
        foreach ($availableWreckage as $report) {
            if (empty($report->wreckage)) {
                continue;
            }

            foreach ($report->wreckage as $machineName => $amount) {
                if ($amount <= 0) {
                    continue;
                }

                try {
                    $shipObject = ObjectService::getUnitObjectByMachineName($machineName);

                    if (!isset($wreckageData[$report->id])) {
                        $wreckageData[$report->id] = [
                            'battle_report_id' => $report->id,
                            'created_at' => $report->created_at,
                            'ships' => [],
                        ];
                    }

                    $wreckageData[$report->id]['ships'][] = [
                        'machine_name' => $machineName,
                        'name' => $shipObject->title,
                        'amount' => $amount,
                    ];
                } catch (Exception $e) {
                    // Skip if ship object not found
                    continue;
                }
            }
        }

        // Prepare repair queue data for view
        $repairQueueData = [];
        foreach ($repairQueue as $repair) {
            try {
                $shipObject = ObjectService::getUnitObjectById($repair->ship_object_id);
                $timeRemaining = max(0, $repair->time_end - now()->timestamp);

                $repairQueueData[] = [
                    'id' => $repair->id,
                    'ship_name' => $shipObject->title,
                    'ship_amount' => $repair->ship_amount,
                    'time_remaining' => $timeRemaining,
                    'time_end' => $repair->time_end,
                ];
            } catch (Exception $e) {
                // Skip if ship object not found
                continue;
            }
        }

        // Prepare ready for pickup data for view
        $readyForPickupData = [];
        foreach ($readyForPickup as $repair) {
            try {
                $shipObject = ObjectService::getUnitObjectById($repair->ship_object_id);

                $readyForPickupData[] = [
                    'id' => $repair->id,
                    'ship_name' => $shipObject->title,
                    'ship_amount' => $repair->ship_amount,
                    'completed_at' => $repair->time_end,
                ];
            } catch (Exception $e) {
                // Skip if ship object not found
                continue;
            }
        }

        return view('ingame.spacedock.index')->with([
            'planet' => $planet,
            'wreckage_data' => $wreckageData,
            'repair_queue' => $repairQueueData,
            'ready_for_pickup' => $readyForPickupData,
            'space_dock_level' => $spaceDockLevel,
        ]);
    }

    /**
     * Start repairing ships from wreckage
     *
     * @param Request $request
     * @param PlayerService $player
     * @param SpaceDockService $spaceDockService
     * @return JsonResponse
     */
    public function startRepair(Request $request, PlayerService $player, SpaceDockService $spaceDockService): JsonResponse
    {
        try {
            $planet = $player->planets->current();

            // Validate request
            $request->validate([
                'battle_report_id' => 'required|integer',
                'ship_machine_name' => 'required|string',
                'amount' => 'required|integer|min:1',
            ]);

            $battleReportId = (int)$request->input('battle_report_id');
            $shipMachineName = $request->input('ship_machine_name');
            $amount = (int)$request->input('amount');

            // Start the repair
            $spaceDockService->startRepair($planet, $battleReportId, $shipMachineName, $amount);

            return response()->json([
                'success' => true,
                'message' => __('Repair started successfully.'),
            ]);
        } catch (Exception $e) {
            // Return 200 to prevent browser console errors for expected failures (duplicates, etc.)
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 200);
        }
    }

    /**
     * Start batch repair - batches all wreckage by ship type into single repairs
     *
     * @param Request $request
     * @param PlayerService $player
     * @param SpaceDockService $spaceDockService
     * @return JsonResponse
     */
    public function startBatchRepair(Request $request, PlayerService $player, SpaceDockService $spaceDockService): JsonResponse
    {
        try {
            $planet = $player->planets->current();

            // Validate request
            $request->validate([
                'wreckage_by_ship' => 'required|array',
                'battle_report_ids' => 'required|array',
            ]);

            $wreckageByShip = $request->input('wreckage_by_ship');
            $battleReportIds = $request->input('battle_report_ids');

            // Start batch repairs (one repair per ship type, combining all battles)
            $spaceDockService->startBatchRepair($planet, $wreckageByShip, $battleReportIds);

            return response()->json([
                'success' => true,
                'message' => __('Batch repair started successfully.'),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 200);
        }
    }

    /**
     * Cancel a repair
     *
     * @param Request $request
     * @param PlayerService $player
     * @param SpaceDockService $spaceDockService
     * @return JsonResponse
     */
    public function cancelRepair(Request $request, PlayerService $player, SpaceDockService $spaceDockService): JsonResponse
    {
        try {
            $planet = $player->planets->current();

            // Validate request
            $request->validate([
                'repair_queue_id' => 'required|integer',
            ]);

            $repairQueueId = (int)$request->input('repair_queue_id');

            // Cancel the repair
            $spaceDockService->cancelRepair($planet, $repairQueueId);

            return response()->json([
                'success' => true,
                'message' => __('Repair canceled successfully.'),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Claim repaired ships (add them to planet)
     *
     * @param Request $request
     * @param PlayerService $player
     * @param SpaceDockService $spaceDockService
     * @return JsonResponse
     */
    public function claimRepairs(Request $request, PlayerService $player, SpaceDockService $spaceDockService): JsonResponse
    {
        try {
            $planet = $player->planets->current();

            // Optional: claim specific repair or all ready repairs
            $repairQueueId = $request->input('repair_queue_id', null);
            if ($repairQueueId !== null) {
                $repairQueueId = (int)$repairQueueId;
            }

            // Optional: amount to claim (for partial collection)
            $amount = $request->input('amount', null);
            if ($amount !== null) {
                $amount = (int)$amount;
            }

            // Claim the repairs (supports partial collection)
            $spaceDockService->claimRepairs($planet, $repairQueueId, $amount);

            return response()->json([
                'success' => true,
                'message' => __('Ships claimed successfully.'),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Dismiss wreckage (let it burn up)
     *
     * @param Request $request
     * @param PlayerService $player
     * @param SpaceDockService $spaceDockService
     * @return JsonResponse
     */
    public function dismissWreckage(Request $request, PlayerService $player, SpaceDockService $spaceDockService): JsonResponse
    {
        try {
            // Validate request
            $request->validate([
                'battle_report_id' => 'required|integer',
            ]);

            $battleReportId = (int)$request->input('battle_report_id');

            // Dismiss the wreckage
            $spaceDockService->dismissWreckage($battleReportId);

            return response()->json([
                'success' => true,
                'message' => __('Wreckage dismissed successfully.'),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
