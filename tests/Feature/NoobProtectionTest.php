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
        // Store the attacker's ID
        $attackerId = $this->currentUserId;

        // Get a foreign planet (this will create a second user if needed)
        $foreignPlanet = $this->getNearbyForeignPlanet();
        $foreignPlayerId = $foreignPlanet->getPlayer()->getId();

        // Switch back to attacking user
        $this->reloadApplication();
        \Auth::loginUsingId($attackerId);
        $this->retrieveMetaFields();

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
        $response->assertJson([
            'status' => 'failure',
        ]);
        $response->assertJsonFragment(['The target player is under noob protection and cannot be attacked.']);
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
        $response->assertJson([
            'status' => 'failure',
        ]);
        $response->assertJsonFragment(['The target player is under noob protection and cannot be attacked.']);
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
        $foreignPlanet->addUnit(ObjectService::getDefenseObjectByMachineName('rocket_launcher'), 10);

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
        $response->assertJsonFragment(['orders' => [
            1 => true, // Attack should be enabled
        ]]);
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
        $foreignPlanet->addUnit(ObjectService::getDefenseObjectByMachineName('rocket_launcher'), 10);

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
        $foreignPlanet->addUnit(ObjectService::getDefenseObjectByMachineName('rocket_launcher'), 10);

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
        $foreignPlanet->addUnit(ObjectService::getDefenseObjectByMachineName('rocket_launcher'), 10);

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
        $foreignPlanet->addUnit(ObjectService::getDefenseObjectByMachineName('rocket_launcher'), 10);

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

        // Try to spy - should be blocked
        $response = $this->post('/ajax/fleet/dispatch/check-target', [
            'galaxy' => $foreignPlanet->getPlanetCoordinates()->galaxy,
            'system' => $foreignPlanet->getPlanetCoordinates()->system,
            'position' => $foreignPlanet->getPlanetCoordinates()->position,
            'type' => PlanetType::Planet->value,
            'mission' => 6, // Espionage
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'failure',
        ]);
        $response->assertJsonFragment(['The target player is under noob protection and cannot be spied on.']);
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
        $response->assertJson([
            'status' => 'failure',
        ]);
        $response->assertJsonFragment(['The target player is under noob protection and cannot be attacked.']);
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
        $response = $this->get('/ajax/galaxy/content', [
            'galaxy' => $foreignPlanet->getPlanetCoordinates()->galaxy,
            'system' => $foreignPlanet->getPlanetCoordinates()->system,
        ]);

        $response->assertStatus(200);
        $data = $response->json();

        // Find the foreign planet in the response
        $planetData = null;
        foreach ($data as $row) {
            if (isset($row['planet']) && $row['planet']['playerId'] === $foreignPlayerId) {
                $planetData = $row['planet'];
                break;
            }
        }

        // Assert that the player is marked as newbie
        $this->assertNotNull($planetData);
        $this->assertTrue($planetData['player']['isNewbie']);
        $this->assertFalse($planetData['player']['isStrong']);
    }
}
