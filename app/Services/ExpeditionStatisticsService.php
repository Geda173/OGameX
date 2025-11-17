<?php

namespace OGame\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use OGame\GameMissions\Models\ExpeditionOutcomeType;
use OGame\Models\FleetMission;
use OGame\Models\Message;
use OGame\Models\User;

/**
 * Service class for gathering and analyzing expedition statistics.
 */
class ExpeditionStatisticsService
{
    /**
     * Get player rankings by expedition count within a time frame.
     *
     * @param Carbon|null $from Start date (null for all time)
     * @param Carbon|null $to End date (null for now)
     * @param int $limit Maximum number of players to return
     * @return array<array{
     *     user_id: int,
     *     username: string,
     *     expedition_count: int,
     *     completed_count: int,
     *     in_progress_count: int,
     *     success_rate: float,
     *     total_profit: int,
     *     total_loss: int,
     *     net_profit: int
     * }>
     */
    public function getPlayerRankings(?Carbon $from = null, ?Carbon $to = null, int $limit = 50): array
    {
        // Build query to get expedition counts per user
        $query = FleetMission::where('mission_type', 15);

        if ($from) {
            $query->where('created_at', '>=', $from);
        }
        if ($to) {
            $query->where('created_at', '<=', $to);
        }

        $expeditionCounts = $query->select('user_id', DB::raw('count(*) as total_count'))
            ->groupBy('user_id')
            ->orderBy('total_count', 'desc')
            ->limit($limit)
            ->get();

        $rankings = [];

        foreach ($expeditionCounts as $row) {
            $userId = $row->user_id;

            // Get user info
            $user = User::find($userId);
            if (!$user) {
                continue;
            }

            // Get detailed statistics for this user
            $stats = $this->getPlayerStatistics($userId, $from, $to);

            $rankings[] = [
                'user_id' => $userId,
                'username' => $user->username,
                'expedition_count' => $stats['total_expeditions'],
                'completed_count' => $stats['completed_expeditions'],
                'in_progress_count' => $stats['in_progress_expeditions'],
                'success_rate' => $stats['success_rate'],
                'total_profit' => $stats['total_profit'],
                'total_loss' => $stats['total_loss'],
                'net_profit' => $stats['net_profit'],
            ];
        }

        return $rankings;
    }

    /**
     * Get comprehensive statistics for a specific player.
     *
     * @param int $userId
     * @param Carbon|null $from
     * @param Carbon|null $to
     * @param bool $debug
     * @return array{
     *     total_expeditions: int,
     *     completed_expeditions: int,
     *     in_progress_expeditions: int,
     *     success_rate: float,
     *     total_profit: int,
     *     total_loss: int,
     *     net_profit: int,
     *     resources_gained: array{metal: int, crystal: int, deuterium: int, dark_matter: int},
     *     ships_gained: array<string, int>,
     *     ships_lost: array<string, int>,
     *     outcomes: array<string, int>,
     *     battles: array{total: int, pirate: int, alien: int},
     *     debug_info: array|null
     * }
     */
    public function getPlayerStatistics(int $userId, ?Carbon $from = null, ?Carbon $to = null, bool $debug = false): array
    {
        // Get expedition counts
        $expeditionQuery = FleetMission::where('mission_type', 15)
            ->where('user_id', $userId);

        if ($from) {
            $expeditionQuery->where('created_at', '>=', $from);
        }
        if ($to) {
            $expeditionQuery->where('created_at', '<=', $to);
        }

        $totalExpeditions = $expeditionQuery->count();
        $completedExpeditions = (clone $expeditionQuery)->where('processed', 1)->count();
        $inProgressExpeditions = (clone $expeditionQuery)->where('processed', 0)->count();

        // Get message-based statistics
        $messageQuery = Message::where('user_id', $userId)
            ->where('tab', 'fleets')
            ->where('subtab', 'expeditions');

        if ($from) {
            $messageQuery->where('created_at', '>=', $from);
        }
        if ($to) {
            $messageQuery->where('created_at', '<=', $to);
        }

        $messages = $messageQuery->get();

        // Calculate outcomes
        $outcomes = [];
        foreach ($messages as $message) {
            $key = $message->key;
            if (!isset($outcomes[$key])) {
                $outcomes[$key] = 0;
            }
            $outcomes[$key]++;
        }

        // Calculate success rate
        $failedOutcomes = [
            ExpeditionOutcomeType::Failed->value,
            ExpeditionOutcomeType::FailedAndDelay->value,
            ExpeditionOutcomeType::FailedAndSpeedup->value,
            ExpeditionOutcomeType::LossOfFleet->value,
        ];

        $failedCount = 0;
        foreach ($failedOutcomes as $outcome) {
            $failedCount += $outcomes[$outcome] ?? 0;
        }

        $totalOutcomes = count($messages);
        $successRate = $totalOutcomes > 0 ? (($totalOutcomes - $failedCount) / $totalOutcomes) * 100 : 0;

        // Calculate resources gained
        $resourcesGained = $this->calculateResourcesGained($messages);

        // Calculate ships gained
        $shipsGained = $this->calculateShipsGained($messages);

        // Calculate ships lost
        $shipsLost = $this->calculateShipsLost($messages);

        // Calculate battles
        $battles = [
            'total' => ($outcomes[ExpeditionOutcomeType::BattlePirates->value] ?? 0) +
                      ($outcomes[ExpeditionOutcomeType::BattleAliens->value] ?? 0) +
                      ($outcomes[ExpeditionOutcomeType::Battle->value] ?? 0),
            'pirate' => $outcomes[ExpeditionOutcomeType::BattlePirates->value] ?? 0,
            'alien' => $outcomes[ExpeditionOutcomeType::BattleAliens->value] ?? 0,
        ];

        // Calculate profit and loss
        $totalProfit = $this->calculateTotalProfit($resourcesGained, $shipsGained);
        $totalLoss = $this->calculateTotalLoss($shipsLost);
        $netProfit = $totalProfit - $totalLoss;

        // Collect debug info if requested
        $debugInfo = null;
        if ($debug) {
            $debugInfo = [
                'total_messages' => count($messages),
                'message_keys' => $messages->pluck('key')->unique()->values()->toArray(),
                'sample_messages' => $messages->take(5)->map(function ($msg) {
                    return [
                        'key' => $msg->key,
                        'params' => $msg->params,
                        'created_at' => $msg->created_at->toDateTimeString(),
                    ];
                })->toArray(),
            ];
        }

        return [
            'total_expeditions' => $totalExpeditions,
            'completed_expeditions' => $completedExpeditions,
            'in_progress_expeditions' => $inProgressExpeditions,
            'success_rate' => round($successRate, 2),
            'total_profit' => $totalProfit,
            'total_loss' => $totalLoss,
            'net_profit' => $netProfit,
            'resources_gained' => $resourcesGained,
            'ships_gained' => $shipsGained,
            'ships_lost' => $shipsLost,
            'outcomes' => $outcomes,
            'battles' => $battles,
            'debug_info' => $debugInfo,
        ];
    }

    /**
     * Calculate total resources gained from messages.
     *
     * @param \Illuminate\Database\Eloquent\Collection $messages
     * @return array{metal: int, crystal: int, deuterium: int, dark_matter: int}
     */
    private function calculateResourcesGained($messages): array
    {
        $metal = 0;
        $crystal = 0;
        $deuterium = 0;
        $darkMatter = 0;

        foreach ($messages as $message) {
            if ($message->key === ExpeditionOutcomeType::GainResources->value) {
                $params = $message->params ?? [];

                // Check for new format: resource_type and resource_amount
                if (isset($params['resource_type']) && isset($params['resource_amount'])) {
                    $amount = (int)$params['resource_amount'];
                    switch ($params['resource_type']) {
                        case 'metal':
                            $metal += $amount;
                            break;
                        case 'crystal':
                            $crystal += $amount;
                            break;
                        case 'deuterium':
                            $deuterium += $amount;
                            break;
                    }
                } else {
                    // Fallback to old format: separate keys
                    $metal += (int)($params['metal'] ?? 0);
                    $crystal += (int)($params['crystal'] ?? 0);
                    $deuterium += (int)($params['deuterium'] ?? 0);
                }
            } elseif ($message->key === ExpeditionOutcomeType::GainDarkMatter->value) {
                $params = $message->params ?? [];
                $darkMatter += (int)($params['dark_matter'] ?? 0);
            }
        }

        return [
            'metal' => $metal,
            'crystal' => $crystal,
            'deuterium' => $deuterium,
            'dark_matter' => $darkMatter,
        ];
    }

    /**
     * Calculate total ships gained from messages.
     *
     * @param \Illuminate\Database\Eloquent\Collection $messages
     * @return array<string, int>
     */
    private function calculateShipsGained($messages): array
    {
        $ships = [];

        foreach ($messages as $message) {
            if ($message->key === ExpeditionOutcomeType::GainShips->value) {
                $params = $message->params ?? [];

                // Check for format with ship details array
                if (isset($params['ships']) && is_array($params['ships'])) {
                    foreach ($params['ships'] as $shipData) {
                        if (isset($shipData['ship_type']) && isset($shipData['amount'])) {
                            $shipType = $shipData['ship_type'];
                            if (!isset($ships[$shipType])) {
                                $ships[$shipType] = 0;
                            }
                            $ships[$shipType] += (int)$shipData['amount'];
                        }
                    }
                } else {
                    // Fallback to old format: direct key-value pairs
                    foreach ($params as $key => $value) {
                        // Skip non-ship parameters
                        if (in_array($key, ['metal', 'crystal', 'deuterium', 'dark_matter', 'subject', 'variation', 'message_variation_id'])) {
                            continue;
                        }

                        if (!isset($ships[$key])) {
                            $ships[$key] = 0;
                        }
                        $ships[$key] += (int)$value;
                    }
                }
            }
        }

        return $ships;
    }

    /**
     * Calculate total ships lost from messages (black holes and battles).
     *
     * @param \Illuminate\Database\Eloquent\Collection $messages
     * @return array<string, int>
     */
    private function calculateShipsLost($messages): array
    {
        $ships = [];

        foreach ($messages as $message) {
            // Black hole destroys entire fleet - would need to get fleet composition from the mission
            // For now, this is not easily accessible from messages alone
            // TODO: Implement loss tracking if fleet composition is stored in message params

            // Battle losses would need battle report data
            // This is complex as we'd need to parse battle reports
        }

        return $ships;
    }

    /**
     * Calculate total profit value (resources + ship values).
     *
     * @param array $resources
     * @param array $ships
     * @return int Total value in resource points
     */
    private function calculateTotalProfit(array $resources, array $ships): int
    {
        $totalValue = $resources['metal'] + $resources['crystal'] + $resources['deuterium'];

        // Add ship values (metal + crystal + deuterium costs)
        foreach ($ships as $shipType => $count) {
            try {
                $unitObject = ObjectService::getUnitObjectByMachineName($shipType);
                $shipCost = $unitObject->price->resources;
                $totalValue += ($shipCost->metal + $shipCost->crystal + $shipCost->deuterium) * $count;
            } catch (\Exception $e) {
                // Ship type not found, skip
                continue;
            }
        }

        return $totalValue;
    }

    /**
     * Calculate total loss value (ship values lost).
     *
     * @param array $ships
     * @return int Total value in resource points
     */
    private function calculateTotalLoss(array $ships): int
    {
        $totalValue = 0;

        foreach ($ships as $shipType => $count) {
            try {
                $unitObject = ObjectService::getUnitObjectByMachineName($shipType);
                $shipCost = $unitObject->price->resources;
                $totalValue += ($shipCost->metal + $shipCost->crystal + $shipCost->deuterium) * $count;
            } catch (\Exception $e) {
                // Ship type not found, skip
                continue;
            }
        }

        return $totalValue;
    }

    /**
     * Get server-wide statistics.
     *
     * @param Carbon|null $from
     * @param Carbon|null $to
     * @return array{
     *     total_expeditions: int,
     *     total_players: int,
     *     average_per_player: float,
     *     most_active_player: array{id: int, username: string, count: int}|null
     * }
     */
    public function getServerStatistics(?Carbon $from = null, ?Carbon $to = null): array
    {
        $query = FleetMission::where('mission_type', 15);

        if ($from) {
            $query->where('created_at', '>=', $from);
        }
        if ($to) {
            $query->where('created_at', '<=', $to);
        }

        $totalExpeditions = $query->count();

        $playerCounts = (clone $query)
            ->select('user_id', DB::raw('count(*) as count'))
            ->groupBy('user_id')
            ->get();

        $totalPlayers = $playerCounts->count();
        $averagePerPlayer = $totalPlayers > 0 ? $totalExpeditions / $totalPlayers : 0;

        $mostActive = $playerCounts->sortByDesc('count')->first();
        $mostActivePlayer = null;

        if ($mostActive) {
            $user = User::find($mostActive->user_id);
            if ($user) {
                $mostActivePlayer = [
                    'id' => $user->id,
                    'username' => $user->username,
                    'count' => $mostActive->count,
                ];
            }
        }

        return [
            'total_expeditions' => $totalExpeditions,
            'total_players' => $totalPlayers,
            'average_per_player' => round($averagePerPlayer, 2),
            'most_active_player' => $mostActivePlayer,
        ];
    }
}
