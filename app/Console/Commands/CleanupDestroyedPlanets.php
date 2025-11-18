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

        foreach ($expiredPlanets as $planet) {
            // Delete the expired destroyed planet
            $planet->delete();
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Successfully cleaned up {$count} expired destroyed planet(s).");
    }
}
