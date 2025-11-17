<?php

namespace Tests\Feature;

use OGame\GameMissions\Models\ExpeditionOutcomeType;
use OGame\Models\Message;
use OGame\Models\FleetMission;
use OGame\Models\Resources;
use OGame\Services\ExpeditionStatisticsService;
use OGame\Services\ObjectService;
use OGame\Services\PlayerService;
use Tests\AccountTestCase;

/**
 * Test that expedition statistics service works as expected.
 */
class ExpeditionStatisticsTest extends AccountTestCase
{
    /**
     * Test that the expedition statistics service returns empty statistics for a new player.
     */
    public function testEmptyStatistics(): void
    {
        $expeditionStatisticsService = resolve(ExpeditionStatisticsService::class);

        /** @var PlayerService $player */
        $player = $this->playerService;

        $statistics = $expeditionStatisticsService->getStatistics($player->getId());

        // Verify overview statistics
        $this->assertEquals(0, $statistics['overview']['total_expeditions']);
        $this->assertEquals(0, $statistics['overview']['completed_expeditions']);
        $this->assertEquals(0, $statistics['overview']['in_progress_expeditions']);
        $this->assertEquals(0.0, $statistics['overview']['success_rate']);

        // Verify outcomes are empty
        $this->assertEmpty($statistics['outcomes']);

        // Verify resources are all zero
        $this->assertEquals(0, $statistics['resources']['total_metal']);
        $this->assertEquals(0, $statistics['resources']['total_crystal']);
        $this->assertEquals(0, $statistics['resources']['total_deuterium']);
        $this->assertEquals(0, $statistics['resources']['total_dark_matter']);

        // Verify ships are empty
        $this->assertEmpty($statistics['ships']);

        // Verify battles are all zero
        $this->assertEquals(0, $statistics['battles']['total_battles']);
        $this->assertEquals(0, $statistics['battles']['pirate_battles']);
        $this->assertEquals(0, $statistics['battles']['alien_battles']);
    }

    /**
     * Test that the expedition statistics service counts expeditions correctly.
     */
    public function testExpeditionCounting(): void
    {
        $expeditionStatisticsService = resolve(ExpeditionStatisticsService::class);

        /** @var PlayerService $player */
        $player = $this->playerService;

        // Create a few expedition missions
        for ($i = 0; $i < 3; $i++) {
            FleetMission::create([
                'user_id' => $player->getId(),
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
            'user_id' => $player->getId(),
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

        $statistics = $expeditionStatisticsService->getStatistics($player->getId());

        // Verify counts
        $this->assertEquals(4, $statistics['overview']['total_expeditions']);
        $this->assertEquals(1, $statistics['overview']['completed_expeditions']);
        $this->assertEquals(3, $statistics['overview']['in_progress_expeditions']);
    }

    /**
     * Test that the expedition statistics service counts resources gained correctly.
     */
    public function testResourcesGained(): void
    {
        $expeditionStatisticsService = resolve(ExpeditionStatisticsService::class);

        /** @var PlayerService $player */
        $player = $this->playerService;

        // Create expedition gain resources messages
        for ($i = 0; $i < 3; $i++) {
            Message::create([
                'user_id' => $player->getId(),
                'key' => ExpeditionOutcomeType::GainResources->value,
                'tab' => 'fleets',
                'subtab' => 'expeditions',
                'params' => [
                    'metal' => 10000,
                    'crystal' => 5000,
                    'deuterium' => 2500,
                ],
                'viewed' => 0,
                'created_at' => now(),
            ]);
        }

        $statistics = $expeditionStatisticsService->getStatistics($player->getId());

        // Verify resource totals
        $this->assertEquals(30000, $statistics['resources']['total_metal']);
        $this->assertEquals(15000, $statistics['resources']['total_crystal']);
        $this->assertEquals(7500, $statistics['resources']['total_deuterium']);
    }

    /**
     * Test that the expedition statistics service counts ships gained correctly.
     */
    public function testShipsGained(): void
    {
        $expeditionStatisticsService = resolve(ExpeditionStatisticsService::class);

        /** @var PlayerService $player */
        $player = $this->playerService;

        // Create expedition gain ships messages
        Message::create([
            'user_id' => $player->getId(),
            'key' => ExpeditionOutcomeType::GainShips->value,
            'tab' => 'fleets',
            'subtab' => 'expeditions',
            'params' => [
                'light_fighter' => 10,
                'heavy_fighter' => 5,
                'cruiser' => 2,
            ],
            'viewed' => 0,
            'created_at' => now(),
        ]);

        Message::create([
            'user_id' => $player->getId(),
            'key' => ExpeditionOutcomeType::GainShips->value,
            'tab' => 'fleets',
            'subtab' => 'expeditions',
            'params' => [
                'light_fighter' => 8,
                'battleship' => 3,
            ],
            'viewed' => 0,
            'created_at' => now(),
        ]);

        $statistics = $expeditionStatisticsService->getStatistics($player->getId());

        // Verify ship totals
        $this->assertEquals(18, $statistics['ships']['light_fighter']);
        $this->assertEquals(5, $statistics['ships']['heavy_fighter']);
        $this->assertEquals(2, $statistics['ships']['cruiser']);
        $this->assertEquals(3, $statistics['ships']['battleship']);
    }

    /**
     * Test that the expedition statistics service calculates outcome distribution correctly.
     */
    public function testOutcomeDistribution(): void
    {
        $expeditionStatisticsService = resolve(ExpeditionStatisticsService::class);

        /** @var PlayerService $player */
        $player = $this->playerService;

        // Create various outcome messages
        $outcomes = [
            ExpeditionOutcomeType::GainResources->value => 5,
            ExpeditionOutcomeType::GainShips->value => 3,
            ExpeditionOutcomeType::Failed->value => 2,
            ExpeditionOutcomeType::BattlePirates->value => 1,
        ];

        foreach ($outcomes as $outcome => $count) {
            for ($i = 0; $i < $count; $i++) {
                Message::create([
                    'user_id' => $player->getId(),
                    'key' => $outcome,
                    'tab' => 'fleets',
                    'subtab' => 'expeditions',
                    'params' => [],
                    'viewed' => 0,
                    'created_at' => now(),
                ]);
            }
        }

        $statistics = $expeditionStatisticsService->getStatistics($player->getId());

        // Total outcomes = 11
        $this->assertEquals(5, $statistics['outcomes'][ExpeditionOutcomeType::GainResources->value]['count']);
        $this->assertEquals(45.45, $statistics['outcomes'][ExpeditionOutcomeType::GainResources->value]['percentage']);

        $this->assertEquals(3, $statistics['outcomes'][ExpeditionOutcomeType::GainShips->value]['count']);
        $this->assertEquals(27.27, $statistics['outcomes'][ExpeditionOutcomeType::GainShips->value]['percentage']);

        $this->assertEquals(2, $statistics['outcomes'][ExpeditionOutcomeType::Failed->value]['count']);
        $this->assertEquals(18.18, $statistics['outcomes'][ExpeditionOutcomeType::Failed->value]['percentage']);

        $this->assertEquals(1, $statistics['outcomes'][ExpeditionOutcomeType::BattlePirates->value]['count']);
        $this->assertEquals(9.09, $statistics['outcomes'][ExpeditionOutcomeType::BattlePirates->value]['percentage']);
    }

    /**
     * Test that the expedition statistics service calculates success rate correctly.
     */
    public function testSuccessRate(): void
    {
        $expeditionStatisticsService = resolve(ExpeditionStatisticsService::class);

        /** @var PlayerService $player */
        $player = $this->playerService;

        // Create successful outcomes
        for ($i = 0; $i < 7; $i++) {
            Message::create([
                'user_id' => $player->getId(),
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
                'user_id' => $player->getId(),
                'key' => ExpeditionOutcomeType::Failed->value,
                'tab' => 'fleets',
                'subtab' => 'expeditions',
                'params' => [],
                'viewed' => 0,
                'created_at' => now(),
            ]);
        }

        $statistics = $expeditionStatisticsService->getStatistics($player->getId());

        // Success rate should be 70% (7 successes out of 10 total)
        $this->assertEquals(70.00, $statistics['overview']['success_rate']);
    }

    /**
     * Test that the expedition statistics service counts battle statistics correctly.
     */
    public function testBattleStatistics(): void
    {
        $expeditionStatisticsService = resolve(ExpeditionStatisticsService::class);

        /** @var PlayerService $player */
        $player = $this->playerService;

        // Create battle messages
        for ($i = 0; $i < 2; $i++) {
            Message::create([
                'user_id' => $player->getId(),
                'key' => ExpeditionOutcomeType::BattlePirates->value,
                'tab' => 'fleets',
                'subtab' => 'expeditions',
                'params' => [],
                'viewed' => 0,
                'created_at' => now(),
            ]);
        }

        for ($i = 0; $i < 3; $i++) {
            Message::create([
                'user_id' => $player->getId(),
                'key' => ExpeditionOutcomeType::BattleAliens->value,
                'tab' => 'fleets',
                'subtab' => 'expeditions',
                'params' => [],
                'viewed' => 0,
                'created_at' => now(),
            ]);
        }

        $statistics = $expeditionStatisticsService->getStatistics($player->getId());

        // Verify battle counts
        $this->assertEquals(5, $statistics['battles']['total_battles']);
        $this->assertEquals(2, $statistics['battles']['pirate_battles']);
        $this->assertEquals(3, $statistics['battles']['alien_battles']);
    }

    /**
     * Test that the API endpoint returns correct JSON response.
     */
    public function testStatisticsApiEndpoint(): void
    {
        $response = $this->get('/ajax/expedition-statistics');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'overview' => [
                'total_expeditions',
                'completed_expeditions',
                'in_progress_expeditions',
                'success_rate',
            ],
            'outcomes',
            'resources' => [
                'total_metal',
                'total_crystal',
                'total_deuterium',
                'total_dark_matter',
            ],
            'ships',
            'battles' => [
                'total_battles',
                'pirate_battles',
                'alien_battles',
            ],
            'timeline',
        ]);
    }

    /**
     * Test that the statistics page loads correctly.
     */
    public function testStatisticsPageLoads(): void
    {
        $response = $this->get('/expedition-statistics');

        $response->assertStatus(200);
        $response->assertSee('Expedition Statistics');
        $response->assertSee('Overview');
        $response->assertSee('Total Expeditions');
        $response->assertSee('Success Rate');
    }
}
