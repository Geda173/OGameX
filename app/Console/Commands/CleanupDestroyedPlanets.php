<?php

namespace OGame\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use OGame\Models\Planet;

class CleanupDestroyedPlanets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ogamex:cleanup-destroyed-planets';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Removes destroyed planets that have expired (destruction timer has passed)';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $currentTime = Carbon::now()->timestamp;

        // Find all destroyed planets where the destruction timer has expired
        $expiredPlanets = Planet::where('destroyed', 1)
            ->where(function ($query) use ($currentTime) {
                $query->where('destroyed_at', '<=', $currentTime)
                    ->orWhereNull('destroyed_at');
            })
            ->get();

        if ($expiredPlanets->isEmpty()) {
            $this->info('No expired destroyed planets found.');
            return;
        }

        $count = $expiredPlanets->count();
        $this->info("Found {$count} expired destroyed planet(s).");

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        // Disable foreign key checks for the entire operation
        $connection = \DB::connection();
        if ($connection->getDriverName() === 'sqlite') {
            $connection->getPdo()->exec('PRAGMA foreign_keys=OFF');
        } else {
            $connection->statement('SET FOREIGN_KEY_CHECKS=0');
        }

        foreach ($expiredPlanets as $planet) {
            // Delete related data first to avoid foreign key constraint violations
            // Delete fleet missions originating from this planet
            \DB::table('fleet_missions')->where('planet_id_from', $planet->id)->delete();
            // Delete fleet missions targeting this planet
            \DB::table('fleet_missions')->where('planet_id_to', $planet->id)->delete();
            // Delete building queue items
            \DB::table('building_queues')->where('planet_id', $planet->id)->delete();
            // Delete unit queue items
            \DB::table('unit_queues')->where('planet_id', $planet->id)->delete();
            // Delete research queue items
            \DB::table('research_queues')->where('planet_id', $planet->id)->delete();
            // Delete repair queue items
            \DB::table('repair_queue')->where('planet_id', $planet->id)->delete();
            // Delete planet relocation records
            \DB::table('planet_relocations')->where('planet_id', $planet->id)->delete();
            // Delete messages related to this planet
            \DB::table('messages')->where('action_planet_id', $planet->id)->delete();
            // Update users who have this as their current planet
            \DB::table('users')->where('planet_current', $planet->id)->update(['planet_current' => null]);

            // Now delete the expired destroyed planet directly via DB to avoid model events
            \DB::table('planets')->where('id', $planet->id)->delete();

            $bar->advance();
        }

        // Re-enable foreign key checks
        if ($connection->getDriverName() === 'sqlite') {
            $connection->getPdo()->exec('PRAGMA foreign_keys=ON');
        } else {
            $connection->statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $bar->finish();
        $this->newLine();
        $this->info("Successfully cleaned up {$count} expired destroyed planet(s).");
    }
}
