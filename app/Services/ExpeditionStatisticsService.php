<?php

namespace OGame\Services;

use Illuminate\Support\Facades\DB;
use OGame\GameMissions\Models\ExpeditionOutcomeType;
use OGame\Models\FleetMission;
use OGame\Models\Message;

/**
 * Service class for gathering and analyzing expedition statistics.
 */
class ExpeditionStatisticsService
{
    /**
     * Get comprehensive expedition statistics for a user.
     *
     * @param int|null $userId User ID to filter statistics for. If null, gets server-wide statistics.
     * @return array{
     *     overview: array{
     *         total_expeditions: int,
     *         completed_expeditions: int,
     *         in_progress_expeditions: int,
     *         success_rate: float
     *     },
     *     outcomes: array<string, array{
     *         count: int,
     *         percentage: float
     *     }>,
     *     resources: array{
     *         total_metal: int,
     *         total_crystal: int,
     *         total_deuterium: int,
     *         total_dark_matter: int
     *     },
     *     ships: array<string, int>,
     *     battles: array{
     *         total_battles: int,
     *         pirate_battles: int,
     *         alien_battles: int
     *     },
     *     timeline: array<array{
     *         date: string,
     *         count: int
     *     }>
     * }
     */
    public function getStatistics(?int $userId = null): array
    {
        $overview = $this->getOverviewStatistics($userId);
        $outcomes = $this->getOutcomeDistribution($userId);
        $resources = $this->getResourcesGained($userId);
        $ships = $this->getShipsGained($userId);
        $battles = $this->getBattleStatistics($userId);
        $timeline = $this->getTimelineStatistics($userId);

        return [
            'overview' => $overview,
            'outcomes' => $outcomes,
            'resources' => $resources,
            'ships' => $ships,
            'battles' => $battles,
            'timeline' => $timeline,
        ];
    }

    /**
     * Get overview statistics for expeditions.
     *
     * @param int|null $userId
     * @return array{
     *     total_expeditions: int,
     *     completed_expeditions: int,
     *     in_progress_expeditions: int,
     *     success_rate: float
     * }
     */
    private function getOverviewStatistics(?int $userId = null): array
    {
        $query = FleetMission::where('mission_type', 15);

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        $totalExpeditions = $query->count();
        $completedExpeditions = (clone $query)->where('processed', 1)->count();
        $inProgressExpeditions = (clone $query)->where('processed', 0)->count();

        // Calculate success rate based on non-failed outcomes
        $successRate = 0.0;
        if ($completedExpeditions > 0) {
            $messageQuery = Message::where('tab', 'fleets')
                ->where('subtab', 'expeditions');

            if ($userId !== null) {
                $messageQuery->where('user_id', $userId);
            }

            $failedOutcomes = [
                ExpeditionOutcomeType::Failed->value,
                ExpeditionOutcomeType::FailedAndDelay->value,
                ExpeditionOutcomeType::FailedAndSpeedup->value,
                ExpeditionOutcomeType::LossOfFleet->value,
            ];

            $failedCount = (clone $messageQuery)->whereIn('key', $failedOutcomes)->count();
            $totalOutcomes = $messageQuery->count();

            if ($totalOutcomes > 0) {
                $successRate = (($totalOutcomes - $failedCount) / $totalOutcomes) * 100;
            }
        }

        return [
            'total_expeditions' => $totalExpeditions,
            'completed_expeditions' => $completedExpeditions,
            'in_progress_expeditions' => $inProgressExpeditions,
            'success_rate' => round($successRate, 2),
        ];
    }

    /**
     * Get distribution of expedition outcomes.
     *
     * @param int|null $userId
     * @return array<string, array{count: int, percentage: float}>
     */
    private function getOutcomeDistribution(?int $userId = null): array
    {
        $query = Message::where('tab', 'fleets')
            ->where('subtab', 'expeditions');

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        $outcomes = $query->select('key', DB::raw('count(*) as count'))
            ->groupBy('key')
            ->get();

        $totalOutcomes = $outcomes->sum('count');
        $distribution = [];

        foreach ($outcomes as $outcome) {
            $percentage = $totalOutcomes > 0 ? ($outcome->count / $totalOutcomes) * 100 : 0;
            $distribution[$outcome->key] = [
                'count' => $outcome->count,
                'percentage' => round($percentage, 2),
            ];
        }

        return $distribution;
    }

    /**
     * Get total resources gained from expeditions.
     *
     * @param int|null $userId
     * @return array{total_metal: int, total_crystal: int, total_deuterium: int, total_dark_matter: int}
     */
    private function getResourcesGained(?int $userId = null): array
    {
        $query = Message::where('tab', 'fleets')
            ->where('subtab', 'expeditions')
            ->where('key', ExpeditionOutcomeType::GainResources->value);

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        $messages = $query->get();

        $totalMetal = 0;
        $totalCrystal = 0;
        $totalDeuterium = 0;

        foreach ($messages as $message) {
            $params = $message->params ?? [];
            $totalMetal += (int)($params['metal'] ?? 0);
            $totalCrystal += (int)($params['crystal'] ?? 0);
            $totalDeuterium += (int)($params['deuterium'] ?? 0);
        }

        // Dark matter from expedition_gain_dark_matter messages
        $darkMatterQuery = Message::where('tab', 'fleets')
            ->where('subtab', 'expeditions')
            ->where('key', ExpeditionOutcomeType::GainDarkMatter->value);

        if ($userId !== null) {
            $darkMatterQuery->where('user_id', $userId);
        }

        $darkMatterMessages = $darkMatterQuery->get();
        $totalDarkMatter = 0;

        foreach ($darkMatterMessages as $message) {
            $params = $message->params ?? [];
            $totalDarkMatter += (int)($params['dark_matter'] ?? 0);
        }

        return [
            'total_metal' => $totalMetal,
            'total_crystal' => $totalCrystal,
            'total_deuterium' => $totalDeuterium,
            'total_dark_matter' => $totalDarkMatter,
        ];
    }

    /**
     * Get total ships gained from expeditions.
     *
     * @param int|null $userId
     * @return array<string, int>
     */
    private function getShipsGained(?int $userId = null): array
    {
        $query = Message::where('tab', 'fleets')
            ->where('subtab', 'expeditions')
            ->where('key', ExpeditionOutcomeType::GainShips->value);

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        $messages = $query->get();

        $ships = [];

        foreach ($messages as $message) {
            $params = $message->params ?? [];

            // Ships are stored in params with ship type as key and count as value
            foreach ($params as $key => $value) {
                // Skip non-ship parameters
                if (in_array($key, ['metal', 'crystal', 'deuterium', 'dark_matter', 'subject', 'variation'])) {
                    continue;
                }

                if (!isset($ships[$key])) {
                    $ships[$key] = 0;
                }
                $ships[$key] += (int)$value;
            }
        }

        return $ships;
    }

    /**
     * Get battle statistics from expeditions.
     *
     * @param int|null $userId
     * @return array{total_battles: int, pirate_battles: int, alien_battles: int}
     */
    private function getBattleStatistics(?int $userId = null): array
    {
        $query = Message::where('tab', 'fleets')
            ->where('subtab', 'expeditions');

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        $pirateBattles = (clone $query)->where('key', ExpeditionOutcomeType::BattlePirates->value)->count();
        $alienBattles = (clone $query)->where('key', ExpeditionOutcomeType::BattleAliens->value)->count();
        $genericBattles = (clone $query)->where('key', ExpeditionOutcomeType::Battle->value)->count();

        return [
            'total_battles' => $pirateBattles + $alienBattles + $genericBattles,
            'pirate_battles' => $pirateBattles,
            'alien_battles' => $alienBattles,
        ];
    }

    /**
     * Get expedition timeline statistics (expeditions per day for the last 30 days).
     *
     * @param int|null $userId
     * @return array<array{date: string, count: int}>
     */
    private function getTimelineStatistics(?int $userId = null, int $days = 30): array
    {
        $query = FleetMission::where('mission_type', 15)
            ->where('created_at', '>=', now()->subDays($days));

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        $expeditions = $query->select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('count(*) as count')
        )
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        return $expeditions->map(function ($item) {
            return [
                'date' => $item->date,
                'count' => $item->count,
            ];
        })->toArray();
    }

    /**
     * Get detailed expedition list with outcomes for a user.
     *
     * @param int $userId
     * @param int $limit
     * @param int $offset
     * @return array{
     *     expeditions: array<array{
     *         id: int,
     *         departure_time: string,
     *         arrival_time: string,
     *         holding_time: int,
     *         processed: bool,
     *         outcome: string|null,
     *         outcome_details: array|null
     *     }>,
     *     total: int
     * }
     */
    public function getDetailedExpeditionList(int $userId, int $limit = 20, int $offset = 0): array
    {
        $query = FleetMission::where('mission_type', 15)
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc');

        $total = $query->count();

        $expeditions = $query->limit($limit)
            ->offset($offset)
            ->get();

        $result = [];

        foreach ($expeditions as $expedition) {
            $outcome = null;
            $outcomeDetails = null;

            // Try to find the outcome message for this expedition
            // Messages are created when the expedition completes
            if ($expedition->processed) {
                // Find message that was created around the same time as the mission's arrival
                $message = Message::where('user_id', $userId)
                    ->where('tab', 'fleets')
                    ->where('subtab', 'expeditions')
                    ->where('created_at', '>=', $expedition->time_arrival->subMinutes(5))
                    ->where('created_at', '<=', $expedition->time_arrival->addMinutes(5))
                    ->first();

                if ($message) {
                    $outcome = $message->key;
                    $outcomeDetails = $message->params;
                }
            }

            $result[] = [
                'id' => $expedition->id,
                'departure_time' => $expedition->time_departure->toDateTimeString(),
                'arrival_time' => $expedition->time_arrival->toDateTimeString(),
                'holding_time' => $expedition->time_holding,
                'processed' => (bool)$expedition->processed,
                'outcome' => $outcome,
                'outcome_details' => $outcomeDetails,
            ];
        }

        return [
            'expeditions' => $result,
            'total' => $total,
        ];
    }
}
