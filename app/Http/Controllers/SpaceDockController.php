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

                    // Calculate repair cost (50% of original cost)
                    $shipPrice = ObjectService::getObjectPrice($machineName, $planet);
                    $repairCostPercentage = 0.5;

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
                        'metal_cost' => (int)($shipPrice->metal->get() * $repairCostPercentage),
                        'crystal_cost' => (int)($shipPrice->crystal->get() * $repairCostPercentage),
                        'deuterium_cost' => (int)($shipPrice->deuterium->get() * $repairCostPercentage),
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
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
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

            // Claim the repairs
            $spaceDockService->claimRepairs($planet, $repairQueueId);

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
