<?php

namespace Tests\Feature;

use Exception;
use OGame\Models\Resources;
use OGame\Services\ObjectService;
use OGame\Services\SettingsService;
use Tests\AccountTestCase;

/**
 * Test building teardown/demolition functionality.
 */
class BuildingTeardownTest extends AccountTestCase
{
    /**
     * Verify that tearing down a metal mine works as expected.
     * @throws Exception
     */
    public function testTeardownMetalMine(): void
    {
        // Set the universe speed to 8x for this test.
        $settingsService = resolve(SettingsService::class);
        $settingsService->set('economy_speed', 8);

        // Build metal mine to level 2 first
        $this->planetSetObjectLevel('metal_mine', 2);
        $this->planetAddResources(new Resources(1000, 1000, 1000, 0));

        // ---
        // Step 1: Issue a request to tear down metal mine
        // ---
        $this->addResourceTeardownRequest('metal_mine');

        // ---
        // Step 2: Verify the building is in the teardown queue
        // ---
        $response = $this->get('/resources');
        $this->assertObjectLevelOnPage($response, 'metal_mine', 2, 'Metal mine is not still at level 2 directly after teardown request issued.');

        // ---
        // Step 3: Verify the building is finished 1 minute later and is now level 1.
        // ---
        $this->travel(1)->minutes();

        $response = $this->get('/resources');
        $this->assertObjectLevelOnPage($response, 'metal_mine', 1, 'Metal mine is not at level 1 one minute after teardown request issued.');
    }

    /**
     * Verify that tearing down a robotics factory on the facilities page works as expected.
     * @throws Exception
     */
    public function testTeardownRoboticsFactory(): void
    {
        // Set the universe speed to 8x for this test.
        $settingsService = resolve(SettingsService::class);
        $settingsService->set('economy_speed', 8);

        // Build robotics factory to level 2 first
        $this->planetSetObjectLevel('robot_factory', 2);
        $this->planetAddResources(new Resources(1000, 1000, 1000, 0));

        // ---
        // Step 1: Issue a request to tear down robotics factory
        // ---
        $this->addFacilitiesTeardownRequest('robot_factory');

        // ---
        // Step 2: Verify the building is in the teardown queue
        // ---
        $response = $this->get('/facilities');
        $this->assertObjectLevelOnPage($response, 'robot_factory', 2, 'Robotics factory is not still at level 2 directly after teardown request issued.');

        // ---
        // Step 3: Verify the building is finished 10 minutes later and is now level 1.
        // ---
        $this->travel(10)->minutes();

        $response = $this->get('/facilities');
        $this->assertObjectLevelOnPage($response, 'robot_factory', 1, 'Robotics factory is not at level 1 ten minutes after teardown request issued.');
    }

    /**
     * Verify that teardown cost calculation is correct without Ion Technology.
     * @throws Exception
     */
    public function testTeardownCostCalculationWithoutIonTech(): void
    {
        // Build metal mine to level 5
        $this->planetSetObjectLevel('metal_mine', 5);

        // Get teardown price for metal mine level 5
        $teardownPrice = ObjectService::getObjectTeardownPrice('metal_mine', $this->planetService);

        // Get build price for metal mine level 5 (which should equal teardown price for level 5)
        $buildPrice = ObjectService::getObjectRawPrice('metal_mine', 5);

        $this->assertEquals($buildPrice->metal->get(), $teardownPrice->metal->get(), 'Teardown metal cost does not match build cost for same level.');
        $this->assertEquals($buildPrice->crystal->get(), $teardownPrice->crystal->get(), 'Teardown crystal cost does not match build cost for same level.');
    }

    /**
     * Verify that teardown cost calculation is correct with Ion Technology.
     * @throws Exception
     */
    public function testTeardownCostCalculationWithIonTech(): void
    {
        // Build metal mine to level 5
        $this->planetSetObjectLevel('metal_mine', 5);

        // Set Ion Technology to level 3 (should give 12% discount)
        $this->playerSetResearchLevel('ion_technology', 3);

        // Get teardown price with Ion Tech
        $teardownPrice = ObjectService::getObjectTeardownPrice('metal_mine', $this->planetService);

        // Get build price for metal mine level 5
        $buildPrice = ObjectService::getObjectRawPrice('metal_mine', 5);

        // Calculate expected price with 12% discount (3 levels * 4%)
        $expectedMetal = floor($buildPrice->metal->get() * 0.88); // 100% - 12% = 88%
        $expectedCrystal = floor($buildPrice->crystal->get() * 0.88);

        $this->assertEquals($expectedMetal, $teardownPrice->metal->get(), 'Teardown metal cost does not apply Ion Technology discount correctly.');
        $this->assertEquals($expectedCrystal, $teardownPrice->crystal->get(), 'Teardown crystal cost does not apply Ion Technology discount correctly.');
    }

    /**
     * Verify that Ion Technology level 25 gives 100% discount (free teardown).
     * @throws Exception
     */
    public function testTeardownCostWithMaxIonTech(): void
    {
        // Build metal mine to level 5
        $this->planetSetObjectLevel('metal_mine', 5);

        // Set Ion Technology to level 25 (should give 100% discount)
        $this->playerSetResearchLevel('ion_technology', 25);

        // Get teardown price with max Ion Tech
        $teardownPrice = ObjectService::getObjectTeardownPrice('metal_mine', $this->planetService);

        // All costs should be 0
        $this->assertEquals(0, $teardownPrice->metal->get(), 'Teardown should be free with Ion Technology level 25.');
        $this->assertEquals(0, $teardownPrice->crystal->get(), 'Teardown should be free with Ion Technology level 25.');
        $this->assertEquals(0, $teardownPrice->deuterium->get(), 'Teardown should be free with Ion Technology level 25.');
    }

    /**
     * Verify that tearing down a building at level 0 fails.
     * @throws Exception
     */
    public function testCannotTeardownLevelZero(): void
    {
        $this->planetAddResources(new Resources(1000, 1000, 1000, 0));

        // Try to tear down metal mine at level 0
        $canTeardown = ObjectService::canTeardown('metal_mine', $this->planetService);

        $this->assertFalse($canTeardown, 'Should not be able to tear down a building at level 0.');
    }

    /**
     * Verify that Terraformer cannot be torn down.
     * @throws Exception
     */
    public function testCannotTeardownTerraformer(): void
    {
        // Build terraformer to level 1
        $this->planetSetObjectLevel('terraformer', 1);

        $canTeardown = ObjectService::canTeardown('terraformer', $this->planetService);

        $this->assertFalse($canTeardown, 'Terraformer should not be allowed to be torn down.');
    }

    /**
     * Verify that Lunar Base cannot be torn down.
     * @throws Exception
     */
    public function testCannotTeardownLunarBase(): void
    {
        // Create a moon for the planet
        $planetServiceFactory = resolve(\OGame\Factories\PlanetServiceFactory::class);
        $moon = $planetServiceFactory->createMoonForPlanet($this->planetService);

        // Build lunar base to level 1
        $moon->setObjectLevel(ObjectService::getObjectByMachineName('lunar_base')->id, 1);

        $canTeardown = ObjectService::canTeardown('lunar_base', $moon);

        $this->assertFalse($canTeardown, 'Lunar Base should not be allowed to be torn down.');
    }

    /**
     * Verify that Research Lab cannot be torn down when research is in progress.
     * @throws Exception
     */
    public function testCannotTeardownResearchLabWhileResearching(): void
    {
        // Set up research lab
        $this->planetSetObjectLevel('research_lab', 2);
        $this->planetAddResources(new Resources(5000, 5000, 5000, 0));

        // Start research
        $this->addResearchBuildRequest('energy_technology');

        // Try to tear down research lab
        $canTeardown = ObjectService::canTeardown('research_lab', $this->planetService);

        $this->assertFalse($canTeardown, 'Research Lab should not be allowed to be torn down while research is in progress.');
    }

    /**
     * Verify that Shipyard cannot be torn down when ships are being built.
     * @throws Exception
     */
    public function testCannotTeardownShipyardWhileBuildingShips(): void
    {
        // Set up shipyard
        $this->planetSetObjectLevel('shipyard', 2);
        $this->planetAddResources(new Resources(10000, 10000, 10000, 0));

        // Start building a ship
        $this->post('/shipyard/add-buildrequest', [
            '_token' => csrf_token(),
            'technologyId' => ObjectService::getObjectByMachineName('small_cargo')->id,
            'amount' => 1,
        ]);

        // Try to tear down shipyard
        $canTeardown = ObjectService::canTeardown('shipyard', $this->planetService);

        $this->assertFalse($canTeardown, 'Shipyard should not be allowed to be torn down while ships are being built.');
    }

    /**
     * Verify that multiple teardowns can be queued.
     * @throws Exception
     */
    public function testMultipleTeardownsInQueue(): void
    {
        // Set the universe speed to 8x for this test.
        $settingsService = resolve(SettingsService::class);
        $settingsService->set('economy_speed', 8);

        // Build metal mine to level 3
        $this->planetSetObjectLevel('metal_mine', 3);
        $this->planetAddResources(new Resources(10000, 10000, 10000, 0));

        // Queue two teardowns
        $this->addResourceTeardownRequest('metal_mine');
        $this->addResourceTeardownRequest('metal_mine');

        // Verify the building is still at level 3
        $response = $this->get('/resources');
        $this->assertObjectLevelOnPage($response, 'metal_mine', 3, 'Metal mine should still be at level 3.');

        // Wait for first teardown to complete
        $this->travel(1)->minutes();

        // Verify the building is now level 2
        $response = $this->get('/resources');
        $this->assertObjectLevelOnPage($response, 'metal_mine', 2, 'Metal mine should be at level 2 after first teardown.');

        // Wait for second teardown to complete
        $this->travel(1)->minutes();

        // Verify the building is now level 1
        $response = $this->get('/resources');
        $this->assertObjectLevelOnPage($response, 'metal_mine', 1, 'Metal mine should be at level 1 after second teardown.');
    }

    /**
     * Verify that teardown time calculation works correctly.
     * @throws Exception
     */
    public function testTeardownTimeCalculation(): void
    {
        // Build metal mine to level 5
        $this->planetSetObjectLevel('metal_mine', 5);

        // Get teardown time
        $teardownTime = $this->planetService->getBuildingTeardownTime('metal_mine');

        // Teardown time should be greater than 0
        $this->assertGreaterThan(0, $teardownTime, 'Teardown time should be greater than 0.');
    }

    /**
     * Verify that teardown time with high economy speed is at least 1 second.
     * @throws Exception
     */
    public function testTeardownTimeMinimum(): void
    {
        // Set very high economy speed
        $settingsService = resolve(SettingsService::class);
        $settingsService->set('economy_speed', 1000);

        // Set robot factory to level 99 for very fast times
        $this->planetSetObjectLevel('robot_factory', 99);
        $this->planetSetObjectLevel('metal_mine', 1);

        // Get teardown time
        $teardownTime = $this->planetService->getBuildingTeardownTime('metal_mine');

        // Teardown time should be at least 1 second (minimum)
        $this->assertEquals(1, $teardownTime, 'Teardown time should be at least 1 second.');
    }

    /**
     * Verify that insufficient resources for teardown prevents it from starting.
     * @throws Exception
     */
    public function testTeardownFailsWithInsufficientResources(): void
    {
        // Build metal mine to level 2
        $this->planetSetObjectLevel('metal_mine', 2);

        // Remove all resources
        $this->planetDeductResources(new Resources(500, 500, 500, 0));

        // Try to tear down (should add to queue but not start)
        $this->addResourceTeardownRequest('metal_mine');

        // Wait some time
        $this->travel(5)->minutes();

        // Verify the building is still at level 2 (teardown didn't start due to lack of resources)
        $response = $this->get('/resources');
        $this->assertObjectLevelOnPage($response, 'metal_mine', 2, 'Metal mine should still be at level 2 due to insufficient resources.');
    }

    /**
     * Verify that build and teardown can coexist in the queue.
     * @throws Exception
     */
    public function testBuildAndTeardownInSameQueue(): void
    {
        // Set the universe speed to 8x for this test.
        $settingsService = resolve(SettingsService::class);
        $settingsService->set('economy_speed', 8);

        // Build metal mine to level 2
        $this->planetSetObjectLevel('metal_mine', 2);
        $this->planetAddResources(new Resources(10000, 10000, 10000, 0));

        // Queue a build request
        $this->addResourceBuildRequest('metal_mine');

        // Wait for it to complete
        $this->travel(1)->minutes();

        // Verify it's now level 3
        $response = $this->get('/resources');
        $this->assertObjectLevelOnPage($response, 'metal_mine', 3, 'Metal mine should be at level 3.');

        // Now queue a teardown
        $this->addResourceTeardownRequest('metal_mine');

        // Wait for it to complete
        $this->travel(1)->minutes();

        // Verify it's back to level 2
        $response = $this->get('/resources');
        $this->assertObjectLevelOnPage($response, 'metal_mine', 2, 'Metal mine should be back to level 2 after teardown.');
    }
}
