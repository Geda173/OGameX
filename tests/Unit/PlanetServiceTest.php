<?php

namespace Tests\Unit;

use OGame\Models\Enums\ResourceType;
use OGame\Models\Resources;
use Tests\AccountTestCase;

class PlanetServiceTest extends AccountTestCase
{
    public function testGetResources(): void
    {
        $this->planetResetResources();
        $this->planetAddResources(new Resources(1000, 2000, 3000, 0));

        $this->assertEquals(1000, $this->planetService->metal()->get());
        $this->assertEquals(2000, $this->planetService->crystal()->get());
        $this->assertEquals(3000, $this->planetService->deuterium()->get());
        $this->assertEquals(0, $this->planetService->energy()->get());
    }

    /**
     * Test for espionage report getXXXArray() methods.
     */
    public function testGetObjectArrays(): void
    {
        $this->planetSetObjectLevel('metal_mine', 1);
        $this->planetSetObjectLevel('crystal_mine', 2);
        $this->planetAddUnit('small_cargo', 10);
        $this->planetAddUnit('destroyer', 3);
        $this->planetAddUnit('espionage_probe', 2);
        $this->planetAddUnit('rocket_launcher', 1);

        // Verify that getBuildingArray() returns the correct array.
        $this->assertEquals([
            'metal_mine' => 1,
            'crystal_mine' => 2,
        ], $this->planetService->getBuildingArray());

        // Verify that getShipArray() returns the correct array.
        $this->assertEquals([
            'small_cargo' => 10,
            'destroyer' => 3,
            'espionage_probe' => 2,
        ], $this->planetService->getShipUnits()->toArray());

        // Verify that getDefenseArray() returns the correct array.
        $this->assertEquals([
            'rocket_launcher' => 1,
        ], $this->planetService->getDefenseUnits()->toArray());
    }

    /**
     * Test that deducting too many resources from planet throws an exception.
     */
    public function testDeductTooManyResources(): void
    {
        $this->planetSetObjectLevel('metal_mine', 1);

        // Specify the type of exception you expect to be thrown
        $this->expectException(\Exception::class);

        // Call the method that should throw the exception
        $this->planetService->deductResources(new Resources(9999, 9999, 9999, 0));
    }

    public function testAddValidResourceIndividually(): void
    {
        $this->planetResetResources();
        $this->planetAddResources(new Resources(1000, 2000, 3000, 0));

        foreach (ResourceType::cases() as $validResource) {
            $this->planetService->addResource($validResource, 100, false);
        }
        $this->assertEquals([
            'metal' => 1100,
            'crystal' => 2100,
            'deuterium' => 3100,
        ], [
            'metal' => $this->planetService->metal()->get(),
            'crystal' => $this->planetService->crystal()->get(),
            'deuterium' => $this->planetService->deuterium()->get(),
        ]);
    }

    /**
     * Test that the field max function returns expected values
     */
    public function testGetPlanetFieldMax(): void
    {
        // Reset terraformer and lunar_base to 0 to ensure clean state
        $this->planetSetObjectLevel('terraformer', 0);
        $this->planetSetObjectLevel('lunar_base', 0);

        \DB::table('planets')->where('id', $this->currentPlanetId)->update(['field_max' => 90]);
        $planetServiceFactory = resolve(\OGame\Factories\PlanetServiceFactory::class);
        $this->planetService = $planetServiceFactory->make($this->currentPlanetId, true);
        $this->planetService->reloadPlanet();

        $this->assertEquals(90, $this->planetService->getPlanetFieldMax());

        \DB::table('planets')->where('id', $this->currentPlanetId)->update(['field_max' => 14]);
        $this->planetService = $planetServiceFactory->make($this->currentPlanetId, true);
        $this->planetService->reloadPlanet();

        $this->assertEquals(14, $this->planetService->getPlanetFieldMax());
    }

    /**
     * Test that the field max function with terraformer (for planets) returns expected values
     */
    public function testGetPlanetFieldMaxWithTerraformer(): void
    {
        $planetServiceFactory = resolve(\OGame\Factories\PlanetServiceFactory::class);

        // Reset terraformer and lunar_base to 0 to ensure clean state
        $this->planetSetObjectLevel('terraformer', 0);
        $this->planetSetObjectLevel('lunar_base', 0);

        // Test none divisible by 2-- should only add 5.
        $this->planetSetObjectLevel('terraformer', 1);
        \DB::table('planets')->where('id', $this->currentPlanetId)->update(['field_max' => 90]);
        $this->planetService = $planetServiceFactory->make($this->currentPlanetId, true);
        $this->planetService->reloadPlanet();

        $this->assertEquals(95, $this->planetService->getPlanetFieldMax(), 'Terraformer level 1 should add 5 to the max fields.');

        // Test a divisible of 2, should add 5, and +1 bonus.
        $this->planetSetObjectLevel('terraformer', 2);
        \DB::table('planets')->where('id', $this->currentPlanetId)->update(['field_max' => 150]);
        $this->planetService = $planetServiceFactory->make($this->currentPlanetId, true);
        $this->planetService->reloadPlanet();

        $this->assertEquals(161, $this->planetService->getPlanetFieldMax(), 'Terraformer level 2 should add 11 to the max fields.');

        // Larger divisible
        $this->planetSetObjectLevel('terraformer', 20);
        \DB::table('planets')->where('id', $this->currentPlanetId)->update(['field_max' => 100]);
        $this->planetService = $planetServiceFactory->make($this->currentPlanetId, true);
        $this->planetService->reloadPlanet();

        // each level + 5 max fields - 100 base, plus 20*5 = 200
        // every 2 levels + 1 max field- 20/2 = 10, so 200 + 10 = 210
        $this->assertEquals(210, $this->planetService->getPlanetFieldMax(), 'Terraformer level 20 should add 210 to the max fields.');

        // Ensure if it's not built it doesn't alter the max fields.
        $this->planetSetObjectLevel('terraformer', 0);
        \DB::table('planets')->where('id', $this->currentPlanetId)->update(['field_max' => 100]);
        $this->planetService = $planetServiceFactory->make($this->currentPlanetId, true);
        $this->planetService->reloadPlanet();

        $this->assertEquals(100, $this->planetService->getPlanetFieldMax(), 'Terraformer level 0 should not alter the max fields.');
    }

    /**
     * Test that the field max function with lunar base (for moons) returns expected values
     */
    public function testGetPlanetFieldMaxWithLunarBase(): void
    {
        $planetServiceFactory = resolve(\OGame\Factories\PlanetServiceFactory::class);

        // Test lunar base level 0 for baseline.
        $this->planetSetObjectLevel('lunar_base', 0);
        $this->planetSetObjectLevel('terraformer', 0); // Reset from previous test
        \DB::table('planets')->where('id', $this->currentPlanetId)->update(['field_max' => 90]);
        $this->planetService = $planetServiceFactory->make($this->currentPlanetId, true);
        $this->planetService->reloadPlanet();

        $this->assertEquals(0, $this->planetService->getBuildingCount());
        $this->assertEquals(90, $this->planetService->getPlanetFieldMax(), 'Lunar base level 0 should not alter the max fields.');

        // Test lunar base level 1-- should add 3 (lunar base itself takes up one field so 2 bonus).
        $this->planetSetObjectLevel('lunar_base', 1);
        \DB::table('planets')->where('id', $this->currentPlanetId)->update(['field_max' => 90]);
        $this->planetService = $planetServiceFactory->make($this->currentPlanetId, true);
        $this->planetService->reloadPlanet();

        $this->assertEquals(1, $this->planetService->getBuildingCount());
        $this->assertEquals(93, $this->planetService->getPlanetFieldMax(), 'Lunar base level 1 should add 3 to the max fields.');

        // Test lunar base level 2-- should add 6.
        $this->planetSetObjectLevel('lunar_base', 2);
        \DB::table('planets')->where('id', $this->currentPlanetId)->update(['field_max' => 150]);
        $this->planetService = $planetServiceFactory->make($this->currentPlanetId, true);
        $this->planetService->reloadPlanet();

        $this->assertEquals(2, $this->planetService->getBuildingCount());
        $this->assertEquals(156, $this->planetService->getPlanetFieldMax(), 'Lunar base level 2 should add 6 to the max fields.');
    }

    /**
     * Tests building count returns valid buildings, and specified levels.
     */
    public function testGetPlanetBuildingCount(): void
    {
        $this->planetSetObjectLevel('metal_mine', 50);
        $this->planetSetObjectLevel('crystal_mine', 20);
        $this->planetAddUnit('small_cargo', 10);
        $this->planetAddUnit('destroyer', 3);
        $this->planetAddUnit('espionage_probe', 2);
        $this->planetAddUnit('rocket_launcher', 1);

        // Should only return valid buildings, ( ie metal_mine and crystal_mine )
        $this->assertEquals(70, $this->planetService->getBuildingCount());

        // Do another test to ensure sum is correct.
        $this->planetSetObjectLevel('metal_mine', 50);
        $this->planetSetObjectLevel('crystal_mine', 50);
        $this->planetSetObjectLevel('solar_plant', 50);
        $this->planetAddUnit('destroyer', 3);
        $this->planetAddUnit('espionage_probe', 2);
        $this->planetAddUnit('rocket_launcher', 44);

        // Should only return valid buildings, ( ie metal_mine crystal_mine, solar_plant )
        $this->assertEquals(150, $this->planetService->getBuildingCount());
    }
}
