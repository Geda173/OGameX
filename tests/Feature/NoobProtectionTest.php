<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use OGame\GameObjects\Models\Units\UnitCollection;
use OGame\Models\Enums\PlanetType;
use OGame\Models\Highscore;
use OGame\Models\Resources;
use OGame\Services\ObjectService;
use OGame\Services\PlayerService;
use Tests\AccountTestCase;

/**
 * Test that noob protection works as expected.
 */
class NoobProtectionTest extends AccountTestCase
{
    use RefreshDatabase;

    /**
     * Set up common test components.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Add ships for testing
        $this->planetAddUnit('light_fighter', 10);
        $this->planetAddResources(new Resources(0, 0, 100000, 0));
    }

    /**
     * Helper method to set player's highscore points.
     *
     * @param int $playerId
     * @param int $generalPoints
     * @param int $militaryPoints
     * @param int $militaryRank
     * @return void
     */
    private function setPlayerHighscore(int $playerId, int $generalPoints, int $militaryPoints = 0, int $militaryRank = 999): void
    {
        Highscore::updateOrCreate(
            ['player_id' => $playerId],
            [
                'general' => $generalPoints,
                'economy' => 0,
                'research' => 0,
                'military' => $militaryPoints,
                'general_rank' => 1,
                'economy_rank' => 1,
                'research_rank' => 1,
                'military_rank' => $militaryRank,
            ]
        );
    }

    /**
     * Helper method to make a player inactive.
     *
     * @param int $playerId
     * @return void
     */
    private function makePlayerInactive(int $playerId): void
    {
        \DB::table('users')
            ->where('id', $playerId)
            ->update(['time' => now()->subDays(8)->timestamp]);
    }

    /**
     * Helper method to get a foreign planet and switch back to attacker.
     * Returns array with [attackerId, foreignPlanet, foreignPlayerId]
     *
     * @return array
     */
    private function setupForeignPlanetTest(): array
    {
        // Store the attacker's ID and coordinates
        $attackerId = $this->currentUserId;
        $attackerPlanetId = $this->planetService->getPlanetId();
        $attackerCoordinates = $this->planetService->getPlanetCoordinates();

        // Check if there's already a second user
        $foreignPlayerId = \DB::table('users')
            ->where('id', '!=', $attackerId)
            ->first()?->id;

        if (!$foreignPlayerId) {
            // Create a new user
            $this->createAndLoginUser();
            $this->retrieveMetaFields(); // This updates $this->currentUserId
            $foreignPlayerId = $this->currentUserId;

            // Switch back to the attacker WITHOUT reloading application
            $attackerUser = \OGame\Models\User::find($attackerId);
            $this->actingAs($attackerUser);
            $this->currentUserId = $attackerId;
            $this->currentPlanetId = $attackerPlanetId;

            // Recreate planet service for the attacker
            $planetServiceFactory = resolve(\OGame\Factories\PlanetServiceFactory::class);
            $this->planetService = $planetServiceFactory->make($attackerPlanetId);
        }

        // Check if the foreign player has a planet nearby
        $foreignPlanetId = \DB::table('planets')
            ->where('user_id', $foreignPlayerId)
            ->where('galaxy', $attackerCoordinates->galaxy)
            ->where('planet_type', PlanetType::Planet->value)
            ->whereBetween('system', [$attackerCoordinates->system - 15, $attackerCoordinates->system + 15])
            ->first()?->id;

        if (!$foreignPlanetId) {
            // Create a planet for the foreign player near the attacker
            $coordinate = $this->getNearbyEmptyCoordinate();
            $planetServiceFactory = resolve(\OGame\Factories\PlanetServiceFactory::class);
            $playerServiceFactory = resolve(\OGame\Factories\PlayerServiceFactory::class);
            $foreignPlayer = $playerServiceFactory->make($foreignPlayerId);
            $foreignPlanet = $planetServiceFactory->createAdditionalPlanetForPlayer($foreignPlayer, $coordinate);
        } else {
            $planetServiceFactory = resolve(\OGame\Factories\PlanetServiceFactory::class);
            $foreignPlanet = $planetServiceFactory->make($foreignPlanetId);
        }

        return [$attackerId, $foreignPlanet, $foreignPlayerId];
    }

    /**
     * Test that attack is blocked when target is under 50k points and attacker has 5x more points.
     */
    public function testAttackBlockedUnder50kPoints(): void
    {
        [$attackerId, $foreignPlanet, $foreignPlayerId] = $this->setupForeignPlanetTest();

        // Set highscores
        $this->setPlayerHighscore($attackerId, 40000000, 1000000, 4);
        $this->setPlayerHighscore($foreignPlayerId, 10000, 5000, 500);

        // Try to attack - should be blocked
        $unitCollection = new UnitCollection();
        $unitCollection->addUnit(ObjectService::getUnitObjectByMachineName('light_fighter'), 1);

        $response = $this->post('/ajax/fleet/dispatch/check-target', [
            'galaxy' => $foreignPlanet->getPlanetCoordinates()->galaxy,
            'system' => $foreignPlanet->getPlanetCoordinates()->system,
            'position' => $foreignPlanet->getPlanetCoordinates()->position,
            'type' => PlanetType::Planet->value,
            'mission' => 1, // Attack
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);
        // Check that error message is in errors array
        $data = $response->json();
        $this->assertNotEmpty($data['errors'] ?? []);
        $errorMessages = array_column($data['errors'], 'message');
        $this->assertContains('The target player is under noob protection and cannot be attacked.', $errorMessages);
        // Check that attack mission is disabled
        $this->assertFalse($data['orders'][1] ?? true);
    }

    /**
     * Test that attack is blocked when target is between 50k-500k points and attacker has 10x more points.
     */
    public function testAttackBlockedBetween50kAnd500kPoints(): void
    {
        [$attackerId, $foreignPlanet, $foreignPlayerId] = $this->setupForeignPlanetTest();

        // Set highscores
        $this->setPlayerHighscore($attackerId, 40000000, 1000000, 4);
        $this->setPlayerHighscore($foreignPlayerId, 100000, 10000, 400);

        // Try to attack - should be blocked (40M is more than 10x of 100k)
        $response = $this->post('/ajax/fleet/dispatch/check-target', [
            'galaxy' => $foreignPlanet->getPlanetCoordinates()->galaxy,
            'system' => $foreignPlanet->getPlanetCoordinates()->system,
            'position' => $foreignPlanet->getPlanetCoordinates()->position,
            'type' => PlanetType::Planet->value,
            'mission' => 1, // Attack
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);
        // Check that error message is in errors array
        $data = $response->json();
        $this->assertNotEmpty($data['errors'] ?? []);
        $errorMessages = array_column($data['errors'], 'message');
        $this->assertContains('The target player is under noob protection and cannot be attacked.', $errorMessages);
        // Check that attack mission is disabled
        $this->assertFalse($data['orders'][1] ?? true);
    }

    /**
     * Test that attack is allowed when target is over 500k points.
     */
    public function testAttackAllowedOver500kPoints(): void
    {
        [$attackerId, $foreignPlanet, $foreignPlayerId] = $this->setupForeignPlanetTest();

        // Set highscores
        $this->setPlayerHighscore($attackerId, 40000000, 1000000, 4);
        $this->setPlayerHighscore($foreignPlayerId, 600000, 50000, 300);

        // Add some defenses to the target to prevent empty planet issues
        $foreignPlanet->addUnit('rocket_launcher', 10);

        // Try to attack - should be allowed
        $response = $this->post('/ajax/fleet/dispatch/check-target', [
            'galaxy' => $foreignPlanet->getPlanetCoordinates()->galaxy,
            'system' => $foreignPlanet->getPlanetCoordinates()->system,
            'position' => $foreignPlanet->getPlanetCoordinates()->position,
            'type' => PlanetType::Planet->value,
            'mission' => 1, // Attack
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
        ]);
        // Check that attack mission is enabled
        $data = $response->json();
        $this->assertTrue($data['orders'][1] ?? false);
    }

    /**
     * Test that military highscore exception allows attack within 100 ranks.
     */
    public function testMilitaryHighscoreExceptionWithin100Ranks(): void
    {
        [$attackerId, $foreignPlanet, $foreignPlayerId] = $this->setupForeignPlanetTest();

        // Set highscores
        $this->setPlayerHighscore($attackerId, 40000000, 1000000, 4);
        $this->setPlayerHighscore($foreignPlayerId, 10000, 800000, 50);

        // Add some defenses to the target
        $foreignPlanet->addUnit('rocket_launcher', 10);

        // Try to attack - should be allowed due to military exception
        $response = $this->post('/ajax/fleet/dispatch/check-target', [
            'galaxy' => $foreignPlanet->getPlanetCoordinates()->galaxy,
            'system' => $foreignPlanet->getPlanetCoordinates()->system,
            'position' => $foreignPlanet->getPlanetCoordinates()->position,
            'type' => PlanetType::Planet->value,
            'mission' => 1, // Attack
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
        ]);
    }

    /**
     * Test that military highscore exception allows attack when defender has >50% attacker's military points.
     */
    public function testMilitaryHighscoreExceptionDefenderHas50PercentMilitary(): void
    {
        [$attackerId, $foreignPlanet, $foreignPlayerId] = $this->setupForeignPlanetTest();

        // Set highscores
        $this->setPlayerHighscore($attackerId, 40000000, 1000000, 4);
        $this->setPlayerHighscore($foreignPlayerId, 10000, 600000, 400);

        // Add some defenses to the target
        $foreignPlanet->addUnit('rocket_launcher', 10);

        // Try to attack - should be allowed due to military exception
        $response = $this->post('/ajax/fleet/dispatch/check-target', [
            'galaxy' => $foreignPlanet->getPlanetCoordinates()->galaxy,
            'system' => $foreignPlanet->getPlanetCoordinates()->system,
            'position' => $foreignPlanet->getPlanetCoordinates()->position,
            'type' => PlanetType::Planet->value,
            'mission' => 1, // Attack
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
        ]);
    }

    /**
     * Test that inactive players (7+ days) lose noob protection.
     */
    public function testInactivePlayerLosesNoobProtection(): void
    {
        [$attackerId, $foreignPlanet, $foreignPlayerId] = $this->setupForeignPlanetTest();

        // Set highscores
        $this->setPlayerHighscore($attackerId, 40000000, 1000000, 4);
        $this->setPlayerHighscore($foreignPlayerId, 10000, 5000, 500);

        // Make the foreign player inactive (7+ days)
        $this->makePlayerInactive($foreignPlayerId);

        // Add some defenses to the target
        $foreignPlanet->addUnit('rocket_launcher', 10);

        // Try to attack - should be allowed because player is inactive
        $response = $this->post('/ajax/fleet/dispatch/check-target', [
            'galaxy' => $foreignPlanet->getPlanetCoordinates()->galaxy,
            'system' => $foreignPlanet->getPlanetCoordinates()->system,
            'position' => $foreignPlanet->getPlanetCoordinates()->position,
            'type' => PlanetType::Planet->value,
            'mission' => 1, // Attack
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
        ]);
    }

    /**
     * Test that outlaw status removes noob protection.
     */
    public function testOutlawPlayerLosesNoobProtection(): void
    {
        [$attackerId, $foreignPlanet, $foreignPlayerId] = $this->setupForeignPlanetTest();

        // Set highscores
        $this->setPlayerHighscore($attackerId, 40000000, 1000000, 4);
        $this->setPlayerHighscore($foreignPlayerId, 10000, 5000, 500);

        // Make the foreign player outlaw
        \DB::table('users')
            ->where('id', $foreignPlayerId)
            ->update(['outlaw_until' => now()->addDays(7)]);

        // Add some defenses to the target
        $foreignPlanet->addUnit('rocket_launcher', 10);

        // Try to attack - should be allowed because player is outlaw
        $response = $this->post('/ajax/fleet/dispatch/check-target', [
            'galaxy' => $foreignPlanet->getPlanetCoordinates()->galaxy,
            'system' => $foreignPlanet->getPlanetCoordinates()->system,
            'position' => $foreignPlanet->getPlanetCoordinates()->position,
            'type' => PlanetType::Planet->value,
            'mission' => 1, // Attack
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
        ]);
    }

    /**
     * Test that espionage is blocked when target is under noob protection.
     */
    public function testEspionageBlockedForProtectedPlayer(): void
    {
        [$attackerId, $foreignPlanet, $foreignPlayerId] = $this->setupForeignPlanetTest();

        // Set highscores
        $this->setPlayerHighscore($attackerId, 40000000, 1000000, 4);
        $this->setPlayerHighscore($foreignPlayerId, 10000, 5000, 500);

        // Add espionage probes to the attacker's planet
        $this->planetAddUnit('espionage_probe', 5);

        // Try to spy - should be blocked
        $response = $this->post('/ajax/fleet/dispatch/check-target', [
            'galaxy' => $foreignPlanet->getPlanetCoordinates()->galaxy,
            'system' => $foreignPlanet->getPlanetCoordinates()->system,
            'position' => $foreignPlanet->getPlanetCoordinates()->position,
            'type' => PlanetType::Planet->value,
            'mission' => 6, // Espionage
            'espionage_probe' => 1, // Send 1 probe
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);
        // Check that error message is in errors array
        $data = $response->json();
        $this->assertNotEmpty($data['errors'] ?? []);
        $errorMessages = array_column($data['errors'], 'message');

        // Debug: Show what error messages we actually got
        if (!in_array('The target player is under noob protection and cannot be spied on.', $errorMessages)) {
            $this->fail('Expected noob protection error not found. Actual errors: ' . implode(' | ', $errorMessages));
        }

        // Check that espionage mission is disabled
        $this->assertFalse($data['orders'][6] ?? true);
    }

    /**
     * Test that ACS attack is blocked when target is under noob protection.
     */
    public function testACSAttackBlockedForProtectedPlayer(): void
    {
        [$attackerId, $foreignPlanet, $foreignPlayerId] = $this->setupForeignPlanetTest();

        // Set highscores
        $this->setPlayerHighscore($attackerId, 40000000, 1000000, 4);
        $this->setPlayerHighscore($foreignPlayerId, 10000, 5000, 500);

        // Try to send ACS attack - should be blocked
        $response = $this->post('/ajax/fleet/dispatch/check-target', [
            'galaxy' => $foreignPlanet->getPlanetCoordinates()->galaxy,
            'system' => $foreignPlanet->getPlanetCoordinates()->system,
            'position' => $foreignPlanet->getPlanetCoordinates()->position,
            'type' => PlanetType::Planet->value,
            'mission' => 2, // ACS Attack
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);
        // Check that error message is in errors array
        $data = $response->json();
        $this->assertNotEmpty($data['errors'] ?? []);
        $errorMessages = array_column($data['errors'], 'message');
        $this->assertContains('The target player is under noob protection and cannot be attacked.', $errorMessages);
        // Check that ACS attack mission is disabled
        $this->assertFalse($data['orders'][2] ?? true);
    }

    /**
     * Test that galaxy view shows correct player status indicators.
     */
    public function testGalaxyViewPlayerStatusIndicators(): void
    {
        [$attackerId, $foreignPlanet, $foreignPlayerId] = $this->setupForeignPlanetTest();

        // Set highscores
        $this->setPlayerHighscore($attackerId, 40000000, 1000000, 4);
        $this->setPlayerHighscore($foreignPlayerId, 10000, 5000, 500);

        // Get galaxy view data
        $response = $this->post('/ajax/galaxy', [
            'galaxy' => $foreignPlanet->getPlanetCoordinates()->galaxy,
            'system' => $foreignPlanet->getPlanetCoordinates()->system,
        ]);

        $response->assertStatus(200);
        $data = $response->json();

        // Debug: Check what we got
        $coords = $foreignPlanet->getPlanetCoordinates();
        $galaxyContent = $data['system']['galaxyContent'] ?? [];

        if (empty($galaxyContent)) {
            // Check if the planet actually exists in the database
            $dbPlanet = \DB::table('planets')
                ->where('galaxy', $coords->galaxy)
                ->where('system', $coords->system)
                ->where('planet', $coords->position)
                ->first();

            $this->fail("Galaxy content is empty! Requesting G:{$coords->galaxy} S:{$coords->system}. Foreign planet at position {$coords->position}. DB planet exists: " . ($dbPlanet ? 'yes (user_id: ' . $dbPlanet->user_id . ')' : 'no'));
        }

        // Find the foreign planet in the response
        $planetData = null;
        foreach ($galaxyContent as $row) {
            if (isset($row['planet']) && $row['planet']['playerId'] === $foreignPlayerId) {
                $planetData = $row['planet'];
                break;
            }
        }

        // Debug: Log foreign planet coordinates if not found
        if ($planetData === null) {
            // Log all player IDs in the galaxy content for debugging
            $playerIds = [];
            foreach ($galaxyContent as $row) {
                if (isset($row['planet'])) {
                    $playerIds[] = $row['planet']['playerId'] ?? 'null';
                }
            }
            $this->fail("Foreign planet not found in galaxy view. Planet at {$coords->galaxy}:{$coords->system}:{$coords->position}, Player ID: {$foreignPlayerId}. Found player IDs: " . implode(', ', $playerIds));
        }

        // Assert that the player is marked as newbie
        $this->assertNotNull($planetData);
        $this->assertTrue($planetData['player']['isNewbie']);
        $this->assertFalse($planetData['player']['isStrong']);
    }
}
