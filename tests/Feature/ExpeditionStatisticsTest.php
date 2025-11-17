<?php

namespace Tests\Feature;

use Carbon\Carbon;
use OGame\GameMissions\Models\ExpeditionOutcomeType;
use OGame\Models\FleetMission;
use OGame\Models\Message;
use OGame\Services\ExpeditionStatisticsService;
use Tests\AccountTestCase;

/**
 * Test that expedition statistics service and command work as expected.
 */
class ExpeditionStatisticsTest extends AccountTestCase
{
    /**
     * Test that the service returns empty statistics for a player with no expeditions.
     */
    public function testEmptyStatistics(): void
    {
        $expeditionStatisticsService = resolve(ExpeditionStatisticsService::class);

        $stats = $expeditionStatisticsService->getPlayerStatistics($this->currentUserId);

        // Verify overview statistics
        $this->assertEquals(0, $stats['total_expeditions']);
        $this->assertEquals(0, $stats['completed_expeditions']);
        $this->assertEquals(0, $stats['in_progress_expeditions']);
        $this->assertEquals(0.0, $stats['success_rate']);

        // Verify profit/loss are zero
        $this->assertEquals(0, $stats['total_profit']);
        $this->assertEquals(0, $stats['total_loss']);
        $this->assertEquals(0, $stats['net_profit']);

        // Verify resources are all zero
        $this->assertEquals(0, $stats['resources_gained']['metal']);
        $this->assertEquals(0, $stats['resources_gained']['crystal']);
        $this->assertEquals(0, $stats['resources_gained']['deuterium']);
        $this->assertEquals(0, $stats['resources_gained']['dark_matter']);

        // Verify ships and outcomes are empty
        $this->assertEmpty($stats['ships_gained']);
        $this->assertEmpty($stats['outcomes']);

        // Verify battles are zero
        $this->assertEquals(0, $stats['battles']['total']);
        $this->assertEquals(0, $stats['battles']['pirate']);
        $this->assertEquals(0, $stats['battles']['alien']);
    }

    /**
     * Test that the service counts expeditions correctly.
     */
    public function testExpeditionCounting(): void
    {
        $expeditionStatisticsService = resolve(ExpeditionStatisticsService::class);

        // Create a few expedition missions
        for ($i = 0; $i < 3; $i++) {
            FleetMission::create([
                'user_id' => $this->currentUserId,
                'planet_id_from' => $this->planetService->getPlanetId(),
                'planet_id_to' => null,
                'galaxy_to' => 1,
                'system_to' => 1,
                'position_to' => 16,
                'mission_type' => 15,
                'time_departure' => now(),
                'time_arrival' => now()->addHours(1),
                'time_holding' => 3600,
                'processed' => 0,
                'canceled' => 0,
                'light_fighter' => 10,
                'metal' => 0,
                'crystal' => 0,
                'deuterium' => 1000,
            ]);
        }

        // Create a completed expedition
        FleetMission::create([
            'user_id' => $this->currentUserId,
            'planet_id_from' => $this->planetService->getPlanetId(),
            'planet_id_to' => null,
            'galaxy_to' => 1,
            'system_to' => 1,
            'position_to' => 16,
            'mission_type' => 15,
            'time_departure' => now()->subHours(3),
            'time_arrival' => now()->subHours(2),
            'time_holding' => 3600,
            'processed' => 1,
            'canceled' => 0,
            'light_fighter' => 10,
            'metal' => 0,
            'crystal' => 0,
            'deuterium' => 1000,
        ]);

        $stats = $expeditionStatisticsService->getPlayerStatistics($this->currentUserId);

        // Verify counts
        $this->assertEquals(4, $stats['total_expeditions']);
        $this->assertEquals(1, $stats['completed_expeditions']);
        $this->assertEquals(3, $stats['in_progress_expeditions']);
    }

    /**
     * Test that the service calculates resources gained correctly.
     */
    public function testResourcesGained(): void
    {
        $expeditionStatisticsService = resolve(ExpeditionStatisticsService::class);

        // Create expedition gain resources messages using actual format
        Message::create([
            'user_id' => $this->currentUserId,
            'key' => ExpeditionOutcomeType::GainResources->value,
            'tab' => 'fleets',
            'subtab' => 'expeditions',
            'params' => [
                'message_variation_id' => 1,
                'resource_type' => 'metal',
                'resource_amount' => 10000,
            ],
            'viewed' => 0,
            'created_at' => now(),
        ]);

        Message::create([
            'user_id' => $this->currentUserId,
            'key' => ExpeditionOutcomeType::GainResources->value,
            'tab' => 'fleets',
            'subtab' => 'expeditions',
            'params' => [
                'message_variation_id' => 2,
                'resource_type' => 'crystal',
                'resource_amount' => 5000,
            ],
            'viewed' => 0,
            'created_at' => now(),
        ]);

        Message::create([
            'user_id' => $this->currentUserId,
            'key' => ExpeditionOutcomeType::GainResources->value,
            'tab' => 'fleets',
            'subtab' => 'expeditions',
            'params' => [
                'message_variation_id' => 3,
                'resource_type' => 'deuterium',
                'resource_amount' => 2500,
            ],
            'viewed' => 0,
            'created_at' => now(),
        ]);

        $stats = $expeditionStatisticsService->getPlayerStatistics($this->currentUserId);

        // Verify resource totals
        $this->assertEquals(10000, $stats['resources_gained']['metal']);
        $this->assertEquals(5000, $stats['resources_gained']['crystal']);
        $this->assertEquals(2500, $stats['resources_gained']['deuterium']);

        // Verify profit includes resources
        $this->assertEquals(17500, $stats['total_profit']);
    }

    /**
     * Test that the service calculates ships gained correctly.
     */
    public function testShipsGained(): void
    {
        $expeditionStatisticsService = resolve(ExpeditionStatisticsService::class);

        // Create expedition gain ships messages using fallback format
        Message::create([
            'user_id' => $this->currentUserId,
            'key' => ExpeditionOutcomeType::GainShips->value,
            'tab' => 'fleets',
            'subtab' => 'expeditions',
            'params' => [
                'message_variation_id' => 1,
                'light_fighter' => 10,
                'heavy_fighter' => 5,
            ],
            'viewed' => 0,
            'created_at' => now(),
        ]);

        Message::create([
            'user_id' => $this->currentUserId,
            'key' => ExpeditionOutcomeType::GainShips->value,
            'tab' => 'fleets',
            'subtab' => 'expeditions',
            'params' => [
                'message_variation_id' => 2,
                'light_fighter' => 8,
                'battleship' => 3,
            ],
            'viewed' => 0,
            'created_at' => now(),
        ]);

        $stats = $expeditionStatisticsService->getPlayerStatistics($this->currentUserId);

        // Verify ship totals
        $this->assertEquals(18, $stats['ships_gained']['light_fighter']);
        $this->assertEquals(5, $stats['ships_gained']['heavy_fighter']);
        $this->assertEquals(3, $stats['ships_gained']['battleship']);

        // Verify profit includes ship values
        $this->assertGreaterThan(0, $stats['total_profit']);
    }

    /**
     * Test that the service calculates success rate correctly.
     */
    public function testSuccessRate(): void
    {
        $expeditionStatisticsService = resolve(ExpeditionStatisticsService::class);

        // Create successful outcomes
        for ($i = 0; $i < 7; $i++) {
            Message::create([
                'user_id' => $this->currentUserId,
                'key' => ExpeditionOutcomeType::GainResources->value,
                'tab' => 'fleets',
                'subtab' => 'expeditions',
                'params' => [],
                'viewed' => 0,
                'created_at' => now(),
            ]);
        }

        // Create failed outcomes
        for ($i = 0; $i < 3; $i++) {
            Message::create([
                'user_id' => $this->currentUserId,
                'key' => ExpeditionOutcomeType::Failed->value,
                'tab' => 'fleets',
                'subtab' => 'expeditions',
                'params' => [],
                'viewed' => 0,
                'created_at' => now(),
            ]);
        }

        $stats = $expeditionStatisticsService->getPlayerStatistics($this->currentUserId);

        // Success rate should be 70% (7 successes out of 10 total)
        $this->assertEquals(70.00, $stats['success_rate']);
    }

    /**
     * Test that player rankings work correctly.
     */
    public function testPlayerRankings(): void
    {
        $expeditionStatisticsService = resolve(ExpeditionStatisticsService::class);

        // Create expeditions for current user
        for ($i = 0; $i < 5; $i++) {
            FleetMission::create([
                'user_id' => $this->currentUserId,
                'planet_id_from' => $this->planetService->getPlanetId(),
                'planet_id_to' => null,
                'galaxy_to' => 1,
                'system_to' => 1,
                'position_to' => 16,
                'mission_type' => 15,
                'time_departure' => now(),
                'time_arrival' => now()->addHours(1),
                'time_holding' => 3600,
                'processed' => 1,
                'canceled' => 0,
                'light_fighter' => 10,
                'metal' => 0,
                'crystal' => 0,
                'deuterium' => 1000,
                'created_at' => now(),
            ]);
        }

        $rankings = $expeditionStatisticsService->getPlayerRankings(null, null, 10);

        // Should have at least one player
        $this->assertGreaterThanOrEqual(1, count($rankings));

        // Current user should be in rankings
        $found = false;
        foreach ($rankings as $rank) {
            if ($rank['user_id'] === $this->currentUserId) {
                $found = true;
                $this->assertEquals(5, $rank['expedition_count']);
                break;
            }
        }
        $this->assertTrue($found, 'Current user not found in rankings');
    }

    /**
     * Test that time filtering works correctly.
     */
    public function testTimeFiltering(): void
    {
        $expeditionStatisticsService = resolve(ExpeditionStatisticsService::class);

        // Create old expeditions
        FleetMission::create([
            'user_id' => $this->currentUserId,
            'planet_id_from' => $this->planetService->getPlanetId(),
            'planet_id_to' => null,
            'galaxy_to' => 1,
            'system_to' => 1,
            'position_to' => 16,
            'mission_type' => 15,
            'time_departure' => now()->subDays(10),
            'time_arrival' => now()->subDays(10)->addHours(1),
            'time_holding' => 3600,
            'processed' => 1,
            'canceled' => 0,
            'light_fighter' => 10,
            'metal' => 0,
            'crystal' => 0,
            'deuterium' => 1000,
            'created_at' => now()->subDays(10),
        ]);

        // Create recent expeditions
        for ($i = 0; $i < 3; $i++) {
            FleetMission::create([
                'user_id' => $this->currentUserId,
                'planet_id_from' => $this->planetService->getPlanetId(),
                'planet_id_to' => null,
                'galaxy_to' => 1,
                'system_to' => 1,
                'position_to' => 16,
                'mission_type' => 15,
                'time_departure' => now(),
                'time_arrival' => now()->addHours(1),
                'time_holding' => 3600,
                'processed' => 1,
                'canceled' => 0,
                'light_fighter' => 10,
                'metal' => 0,
                'crystal' => 0,
                'deuterium' => 1000,
                'created_at' => now(),
            ]);
        }

        // Get stats for last 7 days
        $from = Carbon::now()->subDays(7);
        $stats = $expeditionStatisticsService->getPlayerStatistics($this->currentUserId, $from);

        // Should only count recent expeditions
        $this->assertEquals(3, $stats['total_expeditions']);
    }

    /**
     * Test that the artisan command runs without errors.
     */
    public function testCommandRuns(): void
    {
        // Create some test data
        FleetMission::create([
            'user_id' => $this->currentUserId,
            'planet_id_from' => $this->planetService->getPlanetId(),
            'planet_id_to' => null,
            'galaxy_to' => 1,
            'system_to' => 1,
            'position_to' => 16,
            'mission_type' => 15,
            'time_departure' => now(),
            'time_arrival' => now()->addHours(1),
            'time_holding' => 3600,
            'processed' => 1,
            'canceled' => 0,
            'light_fighter' => 10,
            'metal' => 0,
            'crystal' => 0,
            'deuterium' => 1000,
        ]);

        // Test basic command
        $this->artisan('expedition:stats')
            ->assertExitCode(0);

        // Test with player option
        $this->artisan('expedition:stats', ['--player' => $this->currentUserId])
            ->assertExitCode(0);

        // Test with server option
        $this->artisan('expedition:stats', ['--server' => true])
            ->assertExitCode(0);

        // Test with days option
        $this->artisan('expedition:stats', ['--days' => 30])
            ->assertExitCode(0);
    }
}
