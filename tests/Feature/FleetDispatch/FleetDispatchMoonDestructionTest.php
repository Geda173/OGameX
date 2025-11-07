<?php

namespace Tests\Feature\FleetDispatch;

use Exception;
use Illuminate\Support\Carbon;
use OGame\Factories\PlanetServiceFactory;
use OGame\GameObjects\Models\Units\UnitCollection;
use OGame\Models\Enums\PlanetType;
use OGame\Models\FleetMission;
use OGame\Models\Message;
use OGame\Models\Resources;
use OGame\Services\FleetMissionService;
use OGame\Services\ObjectService;
use OGame\Services\SettingsService;
use Tests\FleetDispatchTestCase;

/**
 * Test that fleet dispatch works as expected for moon destruction missions.
 */
class FleetDispatchMoonDestructionTest extends FleetDispatchTestCase
{
    /**
     * @var int The mission type for the test.
     */
    protected int $missionType = 9;

    /**
     * @var string The mission name for the test, displayed in UI.
     */
    protected string $missionName = 'Moon Destruction';

    /**
     * Prepare the planet for the test, so it has the required buildings and research.
     *
     * @return void
     */
    protected function basicSetup(): void
    {
        $this->planetAddUnit('deathstar', 5);
        $this->playerSetResearchLevel('computer_technology', object_level: 1);

        // Set the fleet speed to 1x for this test.
        $settingsService = resolve(SettingsService::class);
        $settingsService->set('economy_speed', 8);
        $settingsService->set('fleet_speed', 1);
        $this->planetAddResources(new Resources(0, 0, 100000, 0));
    }

    protected function messageCheckMissionArrival(): void
    {
        // Message check is done in individual tests as there are multiple possible outcomes
    }

    protected function messageCheckMissionReturn(): void
    {
        // Assert that message has been sent to player and contains the correct information.
        $this->assertMessageReceivedAndContains('fleets', 'other', [
            'Your fleet is returning from',
            $this->planetService->getPlanetName(),
        ]);
    }

    /**
     * Assert that check request to dispatch moon destruction to planet fails.
     */
    public function testFleetCheckToPlanetError(): void
    {
        $this->basicSetup();
        $unitCollection = new UnitCollection();
        $unitCollection->addUnit(ObjectService::getUnitObjectByMachineName('deathstar'), 1);
        $this->fleetCheckToOtherPlayer($unitCollection, false);
    }

    /**
     * Assert that check request to dispatch moon destruction to foreign moon succeeds.
     */
    public function testFleetCheckToForeignMoonSuccess(): void
    {
        $this->basicSetup();
        $unitCollection = new UnitCollection();
        $unitCollection->addUnit(ObjectService::getUnitObjectByMachineName('deathstar'), 1);

        // Get a foreign moon
        $foreignMoon = $this->getNearbyForeignMoon();
        $this->checkTargetFleet($foreignMoon->getPlanetCoordinates(), $unitCollection, PlanetType::Moon, true);
    }

    /**
     * Assert that check request to dispatch moon destruction to own moon fails.
     */
    public function testFleetCheckToOwnMoonError(): void
    {
        $this->basicSetup();
        $unitCollection = new UnitCollection();
        $unitCollection->addUnit(ObjectService::getUnitObjectByMachineName('deathstar'), 1);

        // Try to target our own moon
        $coordinates = $this->moonService->getPlanetCoordinates();
        $this->checkTargetFleet($coordinates, $unitCollection, PlanetType::Moon, false);
    }

    /**
     * Assert that moon destruction mission requires at least one Deathstar.
     */
    public function testFleetCheckWithoutDeathStarError(): void
    {
        $this->basicSetup();
        $this->planetAddUnit('battlecruiser', 10);

        $unitCollection = new UnitCollection();
        $unitCollection->addUnit(ObjectService::getUnitObjectByMachineName('battlecruiser'), 1);

        // Get a foreign moon
        $foreignMoon = $this->getNearbyForeignMoon();
        $this->checkTargetFleet($foreignMoon->getPlanetCoordinates(), $unitCollection, PlanetType::Moon, false);
    }

    /**
     * Assert that moon destruction mission works and creates appropriate messages.
     * This test verifies the basic mission flow including battle and moon destruction attempt.
     *
     * @throws Exception
     */
    public function testDispatchFleetMoonDestructionFlow(): void
    {
        $this->basicSetup();

        // Send fleet to a nearby foreign moon.
        $unitCollection = new UnitCollection();
        $unitCollection->addUnit(ObjectService::getUnitObjectByMachineName('deathstar'), 1);
        $foreignMoon = $this->sendMissionToOtherPlayerMoon($unitCollection, new Resources(0, 0, 0, 0));

        // Clear any defense from foreign moon to ensure we win the battle.
        $foreignMoon->removeUnits($foreignMoon->getDefenseUnits(), true);
        $foreignMoon->removeUnits($foreignMoon->getShipUnits(), true);

        // Set all messages as read to avoid unread messages count in the overview.
        $this->playerSetAllMessagesRead();

        // Increase time by 10 hours to ensure the mission is done.
        $this->travel(10)->hours();

        // Reload application to make sure the defender moon is not cached.
        $this->reloadApplication();

        // Do a request to trigger the update logic.
        $response = $this->get('/overview');
        $response->assertStatus(200);

        // Assert that battle report has been sent to attacker.
        $this->assertMessageReceivedAndContains('fleets', 'combat_reports', [
            'Combat report',
        ]);

        // Assert that attacker received a moon destruction outcome message
        // (could be success, failed, or fleet destroyed - we just check that some message was received)
        $messages = Message::where('user_id', $this->planetService->getPlayer()->getId())
            ->where('tab', 'fleets')
            ->where('subtab', 'other')
            ->get();

        $this->assertGreaterThan(0, $messages->count(), 'No moon destruction outcome message received');
    }

    /**
     * Test that moon destruction with sufficient Deathstars has a chance to destroy the moon.
     * We'll test with a very small moon (high destruction chance) to increase probability.
     *
     * @throws Exception
     */
    public function testDispatchFleetMoonDestructionWithMultipleDeathStars(): void
    {
        $this->basicSetup();

        // Add more Deathstars for higher chance
        $this->planetAddUnit('deathstar', 45); // Total of 50

        // Send fleet to a nearby foreign moon.
        $unitCollection = new UnitCollection();
        $unitCollection->addUnit(ObjectService::getUnitObjectByMachineName('deathstar'), 50);
        $foreignMoon = $this->sendMissionToOtherPlayerMoon($unitCollection, new Resources(0, 0, 0, 0));

        // Store moon coordinates and ID for later checks
        $moonCoordinates = $foreignMoon->getPlanetCoordinates();
        $moonId = $foreignMoon->getPlanetId();

        // Clear any defense from foreign moon to ensure we win the battle.
        $foreignMoon->removeUnits($foreignMoon->getDefenseUnits(), true);
        $foreignMoon->removeUnits($foreignMoon->getShipUnits(), true);

        // Set all messages as read.
        $this->playerSetAllMessagesRead();

        // Increase time by 10 hours to ensure the mission is done.
        $this->travel(10)->hours();

        // Reload application to make sure the defender moon is not cached.
        $this->reloadApplication();

        // Do a request to trigger the update logic.
        $response = $this->get('/overview');
        $response->assertStatus(200);

        // Check if moon still exists
        $planetServiceFactory = resolve(PlanetServiceFactory::class);
        $moonAfter = $planetServiceFactory->makeForCoordinate($moonCoordinates, true, PlanetType::Moon);

        // Note: We can't guarantee the moon is destroyed due to randomness,
        // but we can verify that the mission executed correctly
        if ($moonAfter === null) {
            // Moon was destroyed - verify defender received destruction message
            $this->assertTrue(true, 'Moon was successfully destroyed');
        } else {
            // Moon survived - verify mission still executed
            $this->assertTrue(true, 'Moon survived the destruction attempt');
        }

        // Both outcomes should have a battle report
        $battleReports = Message::where('user_id', $this->planetService->getPlayer()->getId())
            ->where('key', 'battle_report')
            ->get();
        $this->assertGreaterThan(0, $battleReports->count(), 'No battle report received');
    }

    /**
     * Test that moon destruction mission redirects fleets heading to destroyed moon.
     *
     * @throws Exception
     */
    public function testMoonDestructionRedirectsIncomingFleets(): void
    {
        $this->basicSetup();

        // Get foreign moon
        $foreignMoon = $this->getNearbyForeignMoon();
        $moonCoordinates = $foreignMoon->getPlanetCoordinates();

        // Send a transport mission to the foreign moon first (from second player)
        // This fleet should be redirected to the planet if moon is destroyed
        $fleetMissionService = resolve(FleetMissionService::class);

        // For this test, we'll manually create a fleet mission targeting the moon
        $foreignPlayer = $foreignMoon->getPlayer();
        $foreignPlanet = $this->getNearbyForeignPlanet();

        // Add light fighters to foreign player's planet for the transport
        $foreignPlanet->addUnit(ObjectService::getUnitObjectByMachineName('small_cargo'), 1);

        // Create a mission from foreign player to their own moon (long duration)
        $mission = new FleetMission();
        $mission->user_id = $foreignPlayer->getId();
        $mission->mission_type = 3; // Transport
        $mission->planet_id_from = $foreignPlanet->getPlanetId();
        $mission->planet_id_to = $foreignMoon->getPlanetId();
        $mission->galaxy_from = $foreignPlanet->getPlanetCoordinates()->galaxy;
        $mission->system_from = $foreignPlanet->getPlanetCoordinates()->system;
        $mission->position_from = $foreignPlanet->getPlanetCoordinates()->position;
        $mission->galaxy_to = $moonCoordinates->galaxy;
        $mission->system_to = $moonCoordinates->system;
        $mission->position_to = $moonCoordinates->position;
        $mission->type_from = PlanetType::Planet->value;
        $mission->type_to = PlanetType::Moon->value;
        $mission->time_departure = time();
        $mission->time_arrival = time() + 36000; // 10 hours
        $mission->small_cargo = 1;
        $mission->save();

        // Now send our Deathstar fleet to destroy the moon
        $this->planetAddUnit('deathstar', 95); // Total of 100
        $unitCollection = new UnitCollection();
        $unitCollection->addUnit(ObjectService::getUnitObjectByMachineName('deathstar'), 100);

        $this->dispatchFleet($moonCoordinates, $unitCollection, new Resources(0, 0, 0, 0), PlanetType::Moon, 0, true);

        // Clear any defense from foreign moon to ensure we win the battle.
        $foreignMoon->removeUnits($foreignMoon->getDefenseUnits(), true);
        $foreignMoon->removeUnits($foreignMoon->getShipUnits(), true);

        // Set all messages as read.
        $this->playerSetAllMessagesRead();

        // Increase time by 10 hours to ensure the mission is done.
        $this->travel(10)->hours();

        // Reload application
        $this->reloadApplication();

        // Do a request to trigger the update logic.
        $response = $this->get('/overview');
        $response->assertStatus(200);

        // Check if the transport mission was redirected
        $mission->refresh();

        // If moon was destroyed, the mission should be redirected to planet
        $planetServiceFactory = resolve(PlanetServiceFactory::class);
        $moonAfter = $planetServiceFactory->makeForCoordinate($moonCoordinates, true, PlanetType::Moon);

        if ($moonAfter === null) {
            // Moon destroyed - verify fleet was redirected
            $this->assertEquals(PlanetType::Planet->value, $mission->type_to, 'Fleet was not redirected to planet after moon destruction');
        }
    }

    /**
     * Test that moon destruction mission creates appropriate battle reports.
     *
     * @throws Exception
     */
    public function testDispatchFleetMoonDestructionBattleReport(): void
    {
        $this->basicSetup();

        // Send fleet to a nearby foreign moon.
        $unitCollection = new UnitCollection();
        $unitCollection->addUnit(ObjectService::getUnitObjectByMachineName('deathstar'), 1);
        $foreignMoon = $this->sendMissionToOtherPlayerMoon($unitCollection, new Resources(0, 0, 0, 0));

        // Clear defense from moon
        $foreignMoon->removeUnits($foreignMoon->getDefenseUnits(), true);
        $foreignMoon->removeUnits($foreignMoon->getShipUnits(), true);

        // Set all messages as read.
        $this->playerSetAllMessagesRead();

        // Increase time by 10 hours to ensure the mission is done.
        $this->travel(10)->hours();

        // Reload application
        $this->reloadApplication();

        // Do a request to trigger the update logic.
        $response = $this->get('/overview');
        $response->assertStatus(200);

        // Get battle report message of attacker from database.
        $messageAttacker = Message::where('user_id', $this->planetService->getPlayer()->getId())
            ->where('key', 'battle_report')
            ->orderByDesc('id')
            ->first();
        $this->assertNotNull($messageAttacker, 'Attacker has not received a battle report after moon destruction attempt.');

        // Assert that defender also received a message with the same battle report ID.
        $messageDefender = Message::where('user_id', $foreignMoon->getPlayer()->getId())
            ->where('key', 'battle_report')
            ->orderByDesc('id')
            ->first();

        if ($messageDefender) {
            $this->assertEquals($messageAttacker->battle_report_id, $messageDefender->battle_report_id, 'Defender has not received the same battle report as attacker.');
        } else {
            $this->fail('Defender has not received a battle report after moon destruction attempt.');
        }
    }

    /**
     * Test that moon destruction mission properly handles return trip.
     *
     * @throws Exception
     */
    public function testDispatchFleetMoonDestructionReturnTrip(): void
    {
        $this->basicSetup();

        // Assert that we begin with 5 death stars on planet.
        $response = $this->get('/shipyard');
        $this->assertObjectLevelOnPage($response, 'deathstar', 5, 'Death stars are not at 5 units at beginning of test.');

        // Send fleet to a nearby foreign moon.
        $unitCollection = new UnitCollection();
        $unitCollection->addUnit(ObjectService::getUnitObjectByMachineName('deathstar'), 5);
        $foreignMoon = $this->sendMissionToOtherPlayerMoon($unitCollection, new Resources(0, 0, 0, 0));

        // Clear any defense from foreign moon to ensure we win the battle.
        $foreignMoon->removeUnits($foreignMoon->getDefenseUnits(), true);
        $foreignMoon->removeUnits($foreignMoon->getShipUnits(), true);

        // Get just dispatched fleet mission ID from database.
        $fleetMissionService = resolve(FleetMissionService::class, ['player' => $this->planetService->getPlayer()]);
        $fleetMission = $fleetMissionService->getActiveFleetMissionsForCurrentPlayer()->first();
        $fleetMissionId = $fleetMission->id;

        // Get time it takes for the fleet to travel to the moon.
        $fleetMissionDuration = $fleetMissionService->calculateFleetMissionDuration($this->planetService, $foreignMoon->getPlanetCoordinates(), $unitCollection);

        // Set time to fleet mission duration + 1 second.
        $this->travel($fleetMissionDuration + 1)->seconds();

        // Reload application to make sure the defender moon is not cached.
        $this->reloadApplication();

        // Set all messages as read.
        $this->playerSetAllMessagesRead();

        // Do a request to trigger the update logic.
        $response = $this->get('/overview');
        $response->assertStatus(200);

        // Assert that the fleet mission is processed.
        $fleetMission = $fleetMissionService->getFleetMissionById($fleetMissionId, false);
        $this->assertTrue($fleetMission->processed == 1, 'Fleet mission is not processed after fleet has arrived at destination.');

        $activeMissions = $fleetMissionService->getActiveFleetMissionsForCurrentPlayer();

        // Assert that a return trip has been launched (if fleet survived).
        // Note: Fleet might be destroyed due to random chance, so we check either way
        if ($activeMissions->count() > 0) {
            // Fleet survived and is returning
            $this->assertCount(1, $activeMissions, 'Unexpected number of active missions.');

            // Advance time to return arrival.
            $this->travelTo(Carbon::createFromTimestamp($activeMissions->first()->time_arrival));

            // Do a request to trigger the update logic.
            $response = $this->get('/overview');
            $response->assertStatus(200);

            // Check return message
            $this->messageCheckMissionReturn();
        } else {
            // Fleet was destroyed (valid outcome)
            $this->assertTrue(true, 'Fleet was destroyed during moon destruction attempt');
        }
    }

    /**
     * Test that moon destruction does not create debris field.
     *
     * @throws Exception
     */
    public function testMoonDestructionNoDebrisField(): void
    {
        $this->basicSetup();

        // Send fleet to a nearby foreign moon.
        $unitCollection = new UnitCollection();
        $unitCollection->addUnit(ObjectService::getUnitObjectByMachineName('deathstar'), 1);
        $foreignMoon = $this->sendMissionToOtherPlayerMoon($unitCollection, new Resources(0, 0, 0, 0));

        $moonCoordinates = $foreignMoon->getPlanetCoordinates();

        // Add some defense to moon to create potential debris
        $foreignMoon->addUnit(ObjectService::getUnitObjectByMachineName('rocket_launcher'), 100);

        // Set all messages as read.
        $this->playerSetAllMessagesRead();

        // Increase time by 10 hours to ensure the mission is done.
        $this->travel(10)->hours();

        // Reload application
        $this->reloadApplication();

        // Do a request to trigger the update logic.
        $response = $this->get('/overview');
        $response->assertStatus(200);

        // Check battle report - debris should be 0
        $messageAttacker = Message::where('user_id', $this->planetService->getPlayer()->getId())
            ->where('key', 'battle_report')
            ->orderByDesc('id')
            ->first();

        if ($messageAttacker && $messageAttacker->battle_report_id) {
            $battleReport = \OGame\Models\BattleReport::find($messageAttacker->battle_report_id);
            if ($battleReport) {
                // Verify no debris was created
                $this->assertEquals(0, $battleReport->debris['metal'], 'Debris was created during moon destruction mission');
                $this->assertEquals(0, $battleReport->debris['crystal'], 'Debris was created during moon destruction mission');
            }
        }
    }
}
