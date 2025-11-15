<?php

namespace OGame\Console\Commands;

use Illuminate\Console\Command;
use OGame\Models\FleetMission;
use Carbon\Carbon;

class CleanupStuckMissions extends Command
{
    protected $signature = 'fleet:cleanup-stuck-missions {--dry-run : Show what would be cleaned without actually cleaning} {--all : Clean all unprocessed missions regardless of type}';
    protected $description = 'Cleanup stuck fleet missions';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $currentTime = Carbon::now()->timestamp;

        // List ALL unprocessed missions first
        $allUnprocessed = FleetMission::where('processed', 0)->get();
        $this->info("Total unprocessed missions: {$allUnprocessed->count()}");

        if ($allUnprocessed->count() > 0) {
            $this->info("\nAll unprocessed missions:");
            foreach ($allUnprocessed as $mission) {
                $arrivalDate = Carbon::createFromTimestamp($mission->time_arrival)->format('Y-m-d H:i:s');
                $holdingHours = $mission->time_holding ? round($mission->time_holding / 3600, 2) : 0;
                $this->line("  Mission {$mission->id} - Type: {$mission->mission_type}, Arrival: {$arrivalDate}, Holding: {$holdingHours}h");
            }
        }

        // Find stuck ACS Defend missions (mission_type = 5)
        // These are missions where time_arrival + time_holding has passed but they're not processed
        $stuckDefendMissions = FleetMission::where('mission_type', 5)
            ->where('processed', 0)
            ->whereRaw('time_arrival + COALESCE(time_holding, 0) < ?', [$currentTime])
            ->get();

        // Find stuck ACS Attack missions (mission_type = 2)
        // These are missions where time_arrival has passed but they're not processed
        $stuckAttackMissions = FleetMission::where('mission_type', 2)
            ->where('processed', 0)
            ->where('time_arrival', '<', $currentTime)
            ->get();

        // If --all flag is set, mark ALL unprocessed missions as stuck
        if ($this->option('all')) {
            $stuckDefendMissions = FleetMission::where('processed', 0)->get();
            $stuckAttackMissions = collect(); // Empty collection
            $this->warn("--all flag set: Will process ALL {$stuckDefendMissions->count()} unprocessed missions");
        }

        $this->info("Found {$stuckDefendMissions->count()} stuck ACS Defend missions");
        $this->info("Found {$stuckAttackMissions->count()} stuck ACS Attack missions");

        if ($dryRun) {
            $this->warn("DRY RUN - No changes will be made");

            if ($stuckDefendMissions->count() > 0) {
                $this->info("\nStuck ACS Defend missions:");
                foreach ($stuckDefendMissions as $mission) {
                    $shouldReturn = $mission->time_arrival + ($mission->time_holding ?? 0);
                    $hoursOverdue = round(($currentTime - $shouldReturn) / 3600, 2);
                    $this->line("  Mission {$mission->id} - Should have returned {$hoursOverdue}h ago");
                }
            }

            if ($stuckAttackMissions->count() > 0) {
                $this->info("\nStuck ACS Attack missions:");
                foreach ($stuckAttackMissions as $mission) {
                    $hoursOverdue = round(($currentTime - $mission->time_arrival) / 3600, 2);
                    $this->line("  Mission {$mission->id} - Should have arrived {$hoursOverdue}h ago");
                }
            }

            return 0;
        }

        // Process stuck ACS Defend missions - mark as processed so they trigger return
        foreach ($stuckDefendMissions as $mission) {
            $this->info("Processing stuck ACS Defend mission {$mission->id}");

            // Mark as processed - this will trigger the normal return flow when updateMission is called
            $mission->processed = 1;
            $mission->save();

            // Try to process it immediately
            try {
                $fleetMissionService = app(\OGame\Services\FleetMissionService::class);
                $fleetMissionService->updateMission($mission);
                $this->info("  ✓ Processed and returned mission {$mission->id}");
            } catch (\Exception $e) {
                $this->error("  ✗ Failed to process mission {$mission->id}: {$e->getMessage()}");
            }
        }

        // Process stuck ACS Attack missions - mark as processed
        foreach ($stuckAttackMissions as $mission) {
            $this->info("Processing stuck ACS Attack mission {$mission->id}");

            // Mark as processed
            $mission->processed = 1;
            $mission->save();

            // Try to process it immediately
            try {
                $fleetMissionService = app(\OGame\Services\FleetMissionService::class);
                $fleetMissionService->updateMission($mission);
                $this->info("  ✓ Processed mission {$mission->id}");
            } catch (\Exception $e) {
                $this->error("  ✗ Failed to process mission {$mission->id}: {$e->getMessage()}");
                // If processing fails, at least it's marked as processed
            }
        }

        $this->info("\nCleanup complete!");

        return 0;
    }
}
