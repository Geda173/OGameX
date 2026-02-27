<?php

namespace OGame\Console\Commands\Admin;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;
use OGame\Factories\GameMissionFactory;
use OGame\Models\FleetMission;
use OGame\Services\FleetMissionService;
use OGame\Services\MessageService;
use Throwable;

/**
 * Unlock fleet missions that are permanently stuck due to an unprocessed battle.
 *
 * When the Rust battle engine takes too long and the PHP-FPM worker is killed,
 * the attack mission is left with processed=0. Every subsequent page load by
 * either player re-triggers the same computation, locking both accounts.
 *
 * This command finds all such missions and gracefully cancels them, returning
 * the attacker's fleet and resources to their origin planet.
 */
class UnlockStuckMissions extends Command
{
    protected $signature = 'ogamex:admin:unlock-stuck-missions
                            {--dry-run : List stuck missions without making any changes}
                            {--mission-id= : Unlock a single mission by ID instead of all stuck missions}';

    protected $description = 'Unlock fleet missions permanently stuck at processed=0 by returning the fleet to its origin planet.';

    public function handle(FleetMissionService $fleetMissionService, MessageService $messageService, GameMissionFactory $gameMissionFactory): int
    {
        $dryRun = $this->option('dry-run');
        $specificId = $this->option('mission-id');

        // Build query: outbound missions (no parent) that have not been processed
        // and whose arrival time is in the past (they should have completed by now).
        $query = FleetMission::whereNull('parent_id')
            ->where('processed', 0)
            ->where('canceled', 0)
            ->where('time_arrival', '<', Date::now()->timestamp);

        if ($specificId !== null) {
            $query->where('id', (int)$specificId);
        }

        $missions = $query->orderBy('id')->get();

        if ($missions->isEmpty()) {
            $this->info('No stuck missions found.');
            return 0;
        }

        $this->info(sprintf('Found %d stuck mission(s)%s:', $missions->count(), $dryRun ? ' (dry run — no changes will be made)' : ''));
        $this->newLine();

        $headers = ['ID', 'User ID', 'Mission type', 'Arrived at', 'Units'];
        $rows = [];

        foreach ($missions as $mission) {
            $rows[] = [
                $mission->id,
                $mission->user_id,
                $fleetMissionService->missionTypeToLabel($mission->mission_type),
                date('Y-m-d H:i:s', $mission->time_arrival),
                $fleetMissionService->getFleetUnitCount($mission),
            ];
        }

        $this->table($headers, $rows);
        $this->newLine();

        if ($dryRun) {
            $this->warn('Dry run complete. Run without --dry-run to apply changes.');
            return 0;
        }

        if (!$this->confirm(sprintf('Cancel and return %d mission(s) to their origin planets?', $missions->count()))) {
            $this->info('Aborted.');
            return 0;
        }

        $unlocked = 0;
        $failed = 0;

        foreach ($missions as $mission) {
            try {
                $missionObject = $gameMissionFactory->getMissionById($mission->mission_type, [
                    'fleetMissionService' => $fleetMissionService,
                    'messageService' => $messageService,
                ]);
                $missionObject->cancel($mission);

                $this->line(sprintf(
                    '  [OK] Mission #%d (user %d) — fleet returned to origin.',
                    $mission->id,
                    $mission->user_id
                ));
                $unlocked++;
            } catch (Throwable $e) {
                $this->error(sprintf(
                    '  [FAIL] Mission #%d — %s',
                    $mission->id,
                    $e->getMessage()
                ));
                $failed++;
            }
        }

        $this->newLine();
        $this->info(sprintf('Done. %d unlocked, %d failed.', $unlocked, $failed));

        return $failed > 0 ? 1 : 0;
    }
}
