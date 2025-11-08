<?php

namespace OGame\Console\Commands;

use Illuminate\Console\Command;
use OGame\Models\Planet;
use OGame\Models\BuildingQueue;
use OGame\Services\BuildingQueueService;
use OGame\Factories\PlanetServiceFactory;

class DebugTeardown extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debug:teardown';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Debug teardown functionality';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Get the first planet
        $planet = Planet::first();
        if (!$planet) {
            $this->error('No planet found in database');
            return 1;
        }

        $this->info("Testing with Planet ID: {$planet->id}");
        $this->info("Planet name: {$planet->name}");

        // Get a building that exists on this planet
        $planetServiceFactory = resolve(PlanetServiceFactory::class);
        $planetService = $planetServiceFactory->make($planet->id);

        // List available buildings with their levels
        $this->info("\nBuildings on this planet:");
        $buildings = ['metal_mine', 'crystal_mine', 'deuterium_synthesizer', 'solar_plant', 'robot_factory'];

        $selectedBuilding = null;
        $selectedObjectId = null;

        foreach ($buildings as $building) {
            $level = $planetService->getObjectLevel($building);
            $this->info("  {$building}: Level {$level}");

            if ($level > 0 && !$selectedBuilding) {
                $selectedBuilding = $building;
                $obj = \OGame\Services\ObjectService::getObjectByMachineName($building);
                $selectedObjectId = $obj->id;
            }
        }

        if (!$selectedBuilding) {
            $this->error('No building with level > 0 found for testing');
            return 1;
        }

        $this->info("\nAttempting to add teardown request for: {$selectedBuilding} (ID: {$selectedObjectId})");

        // Clear any existing unprocessed queue items
        BuildingQueue::where('planet_id', $planet->id)->where('processed', 0)->delete();

        // Manually create a queue item to see what gets saved
        $this->info("\nTest 1: Manual creation");
        $queue = new BuildingQueue();
        $queue->planet_id = $planet->id;
        $queue->object_id = $selectedObjectId;
        $queue->object_level_target = 1;
        $queue->teardown = 1;

        $this->info("Before save - teardown value: " . $queue->teardown);
        $queue->save();
        $this->info("After save - teardown value: " . $queue->teardown);

        // Reload from database
        $reloaded = BuildingQueue::find($queue->id);
        $this->info("After reload from DB - teardown value: " . $reloaded->teardown);
        $this->info("Full record: " . json_encode($reloaded->toArray()));

        // Clean up
        $reloaded->delete();

        $this->info("\n" . str_repeat('=', 50));
        $this->info("Test 2: Using BuildingQueueService");

        try {
            $buildingQueueService = resolve(BuildingQueueService::class);
            $buildingQueueService->addTeardown($planetService, $selectedObjectId);

            $latest = BuildingQueue::where('planet_id', $planet->id)
                ->where('processed', 0)
                ->orderBy('id', 'desc')
                ->first();

            if ($latest) {
                $this->info("Latest queue item created:");
                $this->info("  ID: {$latest->id}");
                $this->info("  Object ID: {$latest->object_id}");
                $this->info("  Target Level: {$latest->object_level_target}");
                $this->info("  Teardown: {$latest->teardown}");
                $this->info("  Building: {$latest->building}");
                $this->info("\nFull record: " . json_encode($latest->toArray()));
            } else {
                $this->error("No queue item was created!");
            }
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
        }

        return 0;
    }
}
