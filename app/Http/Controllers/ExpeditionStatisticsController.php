<?php

namespace OGame\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use OGame\Services\ExpeditionStatisticsService;

/**
 * Controller for expedition statistics page and API.
 */
class ExpeditionStatisticsController extends OGameController
{
    /**
     * Shows the expedition statistics page.
     *
     * @param Request $request
     * @param ExpeditionStatisticsService $expeditionStatisticsService
     * @return View
     */
    public function index(Request $request, ExpeditionStatisticsService $expeditionStatisticsService): View
    {
        $this->setBodyId('statistics');

        $player = $this->getPlayer();

        // Get statistics for the current player
        $statistics = $expeditionStatisticsService->getStatistics($player->getId());

        return view('ingame.expedition-statistics.index')->with([
            'statistics' => $statistics,
        ]);
    }

    /**
     * Get expedition statistics as JSON (API endpoint).
     *
     * @param Request $request
     * @param ExpeditionStatisticsService $expeditionStatisticsService
     * @return JsonResponse
     */
    public function getStatistics(Request $request, ExpeditionStatisticsService $expeditionStatisticsService): JsonResponse
    {
        $player = $this->getPlayer();

        // Get statistics for the current player
        $statistics = $expeditionStatisticsService->getStatistics($player->getId());

        return response()->json($statistics);
    }

    /**
     * Get detailed expedition list as JSON (API endpoint).
     *
     * @param Request $request
     * @param ExpeditionStatisticsService $expeditionStatisticsService
     * @return JsonResponse
     */
    public function getExpeditionList(Request $request, ExpeditionStatisticsService $expeditionStatisticsService): JsonResponse
    {
        $player = $this->getPlayer();

        $limit = (int) $request->get('limit', 20);
        $offset = (int) $request->get('offset', 0);

        // Validate limit and offset
        $limit = max(1, min($limit, 100)); // Between 1 and 100
        $offset = max(0, $offset);

        $expeditions = $expeditionStatisticsService->getDetailedExpeditionList($player->getId(), $limit, $offset);

        return response()->json($expeditions);
    }

    /**
     * Get server-wide expedition statistics (admin only).
     *
     * @param Request $request
     * @param ExpeditionStatisticsService $expeditionStatisticsService
     * @return JsonResponse
     */
    public function getServerStatistics(Request $request, ExpeditionStatisticsService $expeditionStatisticsService): JsonResponse
    {
        // TODO: Add admin authorization check
        // For now, return server-wide statistics
        $statistics = $expeditionStatisticsService->getStatistics(null);

        return response()->json($statistics);
    }
}
