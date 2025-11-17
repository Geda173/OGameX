<?php

namespace OGame\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use OGame\Models\User;
use OGame\Services\ExpeditionStatisticsService;

class ExpeditionStatistics extends Command
{
    protected $signature = 'expedition:stats
                            {--from= : Start date (Y-m-d format, e.g., 2024-01-01)}
                            {--to= : End date (Y-m-d format, e.g., 2024-12-31)}
                            {--days= : Number of days back from now (alternative to --from)}
                            {--player= : Specific player ID or username to show detailed stats for}
                            {--limit=20 : Maximum number of players to show in rankings (default: 20)}
                            {--server : Show server-wide statistics}';

    protected $description = 'Display expedition statistics and player rankings';

    public function handle(ExpeditionStatisticsService $service): int
    {
        // Parse time range
        $from = null;
        $to = null;

        if ($this->option('days')) {
            $days = (int)$this->option('days');
            $from = Carbon::now()->subDays($days);
            $this->info("Time range: Last {$days} days");
        } elseif ($this->option('from') || $this->option('to')) {
            if ($this->option('from')) {
                $from = Carbon::parse($this->option('from'));
                $this->info("From: {$from->toDateString()}");
            }
            if ($this->option('to')) {
                $to = Carbon::parse($this->option('to'));
                $this->info("To: {$to->toDateString()}");
            }
        } else {
            $this->info("Time range: All time");
        }

        $this->newLine();

        // Show specific player statistics
        if ($this->option('player')) {
            return $this->showPlayerStatistics($service, $from, $to);
        }

        // Show server-wide statistics
        if ($this->option('server')) {
            return $this->showServerStatistics($service, $from, $to);
        }

        // Show player rankings (default)
        return $this->showPlayerRankings($service, $from, $to);
    }

    /**
     * Show player rankings.
     */
    private function showPlayerRankings(ExpeditionStatisticsService $service, ?Carbon $from, ?Carbon $to): int
    {
        $limit = (int)$this->option('limit');
        $rankings = $service->getPlayerRankings($from, $to, $limit);

        if (empty($rankings)) {
            $this->warn('No expedition data found for the specified time range.');
            return 0;
        }

        $this->info("Top {$limit} Players by Expedition Count:");
        $this->newLine();

        $headers = ['Rank', 'Player', 'Expeditions', 'Completed', 'In Progress', 'Success Rate', 'Total Profit', 'Total Loss', 'Net Profit'];
        $rows = [];

        foreach ($rankings as $index => $player) {
            $rows[] = [
                $index + 1,
                $player['username'] . " (ID: {$player['user_id']})",
                number_format($player['expedition_count']),
                number_format($player['completed_count']),
                number_format($player['in_progress_count']),
                $player['success_rate'] . '%',
                number_format($player['total_profit']),
                number_format($player['total_loss']),
                number_format($player['net_profit']),
            ];
        }

        $this->table($headers, $rows);

        $this->newLine();
        $this->info("Use --player=<username> or --player=<id> to see detailed statistics for a specific player.");

        return 0;
    }

    /**
     * Show detailed statistics for a specific player.
     */
    private function showPlayerStatistics(ExpeditionStatisticsService $service, ?Carbon $from, ?Carbon $to): int
    {
        $playerInput = $this->option('player');

        // Try to find player by ID or username
        $player = null;
        if (is_numeric($playerInput)) {
            $player = User::find((int)$playerInput);
        } else {
            $player = User::where('username', $playerInput)->first();
        }

        if (!$player) {
            $this->error("Player '{$playerInput}' not found.");
            return 1;
        }

        $stats = $service->getPlayerStatistics($player->id, $from, $to);

        $this->info("Expedition Statistics for: {$player->username} (ID: {$player->id})");
        $this->newLine();

        // Overview
        $this->line("<fg=cyan>═══ Overview ═══</>");
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Expeditions', number_format($stats['total_expeditions'])],
                ['Completed', number_format($stats['completed_expeditions'])],
                ['In Progress', number_format($stats['in_progress_expeditions'])],
                ['Success Rate', $stats['success_rate'] . '%'],
            ]
        );

        // Profit/Loss
        $this->newLine();
        $this->line("<fg=cyan>═══ Profit & Loss ═══</>");
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Profit', number_format($stats['total_profit'])],
                ['Total Loss', number_format($stats['total_loss'])],
                ['<fg=' . ($stats['net_profit'] >= 0 ? 'green' : 'red') . '>Net Profit</>', '<fg=' . ($stats['net_profit'] >= 0 ? 'green' : 'red') . '>' . number_format($stats['net_profit']) . '</>'],
            ]
        );

        // Resources Gained
        $this->newLine();
        $this->line("<fg=cyan>═══ Resources Gained ═══</>");
        $this->table(
            ['Resource', 'Amount'],
            [
                ['Metal', number_format($stats['resources_gained']['metal'])],
                ['Crystal', number_format($stats['resources_gained']['crystal'])],
                ['Deuterium', number_format($stats['resources_gained']['deuterium'])],
                ['Dark Matter', number_format($stats['resources_gained']['dark_matter'])],
            ]
        );

        // Ships Gained
        if (!empty($stats['ships_gained'])) {
            $this->newLine();
            $this->line("<fg=cyan>═══ Ships Gained ═══</>");
            $shipRows = [];
            foreach ($stats['ships_gained'] as $shipType => $count) {
                $shipRows[] = [ucwords(str_replace('_', ' ', $shipType)), number_format($count)];
            }
            $this->table(['Ship Type', 'Count'], $shipRows);
        }

        // Battles
        if ($stats['battles']['total'] > 0) {
            $this->newLine();
            $this->line("<fg=cyan>═══ Battles ═══</>");
            $this->table(
                ['Battle Type', 'Count'],
                [
                    ['Total Battles', number_format($stats['battles']['total'])],
                    ['Pirate Battles', number_format($stats['battles']['pirate'])],
                    ['Alien Battles', number_format($stats['battles']['alien'])],
                ]
            );
        }

        // Outcomes
        if (!empty($stats['outcomes'])) {
            $this->newLine();
            $this->line("<fg=cyan>═══ Outcome Distribution ═══</>");
            $outcomeRows = [];
            $totalOutcomes = array_sum($stats['outcomes']);
            foreach ($stats['outcomes'] as $outcome => $count) {
                $percentage = $totalOutcomes > 0 ? round(($count / $totalOutcomes) * 100, 2) : 0;
                $outcomeLabel = ucwords(str_replace(['expedition_', '_'], ['', ' '], $outcome));
                $outcomeRows[] = [$outcomeLabel, number_format($count), $percentage . '%'];
            }
            $this->table(['Outcome', 'Count', 'Percentage'], $outcomeRows);
        }

        return 0;
    }

    /**
     * Show server-wide statistics.
     */
    private function showServerStatistics(ExpeditionStatisticsService $service, ?Carbon $from, ?Carbon $to): int
    {
        $stats = $service->getServerStatistics($from, $to);

        $this->info("Server-Wide Expedition Statistics");
        $this->newLine();

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Expeditions', number_format($stats['total_expeditions'])],
                ['Total Players', number_format($stats['total_players'])],
                ['Average per Player', number_format($stats['average_per_player'], 2)],
            ]
        );

        if ($stats['most_active_player']) {
            $this->newLine();
            $this->info("Most Active Player:");
            $this->line("  {$stats['most_active_player']['username']} (ID: {$stats['most_active_player']['id']}) with " . number_format($stats['most_active_player']['count']) . " expeditions");
        }

        return 0;
    }
}
