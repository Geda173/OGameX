<?php

namespace Tests\Unit;

use OGame\Models\Resources;
use OGame\Services\SettingsService;
use Tests\AccountTestCase;

class ResourceProductionTest extends AccountTestCase
{
    /**
     * Set up common test components.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Set the universe speed to 8x for this test.
        $settingsService = resolve(SettingsService::class);
        $settingsService->set('economy_speed', 8);
    }

    /**
     * Test metal mine production with positive energy production.
     */
    public function testMineProduction(): void
    {
        $this->planetSetObjectLevel('metal_mine', 20);
        $this->planetSetObjectLevel('crystal_mine', 20);
        $this->planetSetObjectLevel('deuterium_synthesizer', 20);
        $this->planetSetObjectLevel('solar_plant', 20);

        // Reload and recalculate production after setting all buildings
        $this->planetReloadAndRecalculateProduction();

        // Assertions for production values with positive energy
        $this->assertGreaterThan(1000, $this->planetService->getMetalProductionPerHour());
        $this->assertGreaterThan(1000, $this->planetService->getCrystalProductionPerHour());
        $this->assertGreaterThan(500, $this->planetService->getDeuteriumProductionPerHour());
    }

    /**
     * Test metal mine production with zero energy production.
     */
    public function testMineProductionNoEnergy(): void
    {
        $this->planetSetObjectLevel('metal_mine', 20);
        $this->planetSetObjectLevel('crystal_mine', 20);
        $this->planetSetObjectLevel('deuterium_synthesizer', 20);
        $this->planetSetObjectLevel('solar_plant', 0); // Explicitly set to 0 for no energy

        $position = $this->planetService->getPlanetCoordinates()->position;

        // Get the bonus for the planet position
        $bonus_planet_position_bonuses = $this->planetService->getProductionForPositionBonuses($position);

        // Assertions for production values with zero energy
        $this->assertEquals(240 * $bonus_planet_position_bonuses["metal"], $this->planetService->getMetalProductionPerHour());
        $this->assertEquals(120 * $bonus_planet_position_bonuses["crystal"], $this->planetService->getCrystalProductionPerHour());
        $this->assertEquals(0, $this->planetService->getDeuteriumProductionPerHour());
    }

    /**
     * Test solar satellite energy production.
     */
    public function testSolarSatelliteEnergyProduction(): void
    {
        $this->planetAddUnit('solar_satellite', 100);

        // Set planet temperature
        \DB::table('planets')->where('id', $this->currentPlanetId)->update(['temp_max' => 100]);

        // Reload and recalculate production after setting units and temperature
        $this->planetReloadAndRecalculateProduction();

        $this->assertEquals(4000, $this->planetService->energyProduction()->get());
    }

    /**
     * Test that fusion plant energy production depends on deuterium storage.
     */
    public function testFusionPlantEnergyProductionScalesWithDeuterium(): void
    {
        // Test with positive deuterium production
        $this->planetSetObjectLevel('deuterium_synthesizer', 20);
        $this->planetSetObjectLevel('fusion_plant', 20);
        $this->planetAddResources(new Resources(0, 0, 1000, 0)); // Some deuterium in storage

        $fullEnergy = $this->planetService->energyProduction()->get();
        $this->assertGreaterThan(0, $fullEnergy, 'Fusion plant should produce energy when deuterium storage is above 0');

        // Test with negative deuterium production but deuterium still in storage
        $this->planetSetObjectLevel('deuterium_synthesizer', 0);
        $this->planetSetObjectLevel('fusion_plant', 20);
        $this->planetAddResources(new Resources(0, 0, -500, 0)); // Adjust to 500 deuterium in storage

        $energyWithStorage = $this->planetService->energyProduction()->get();
        $this->assertEquals($fullEnergy, $energyWithStorage, 'Fusion plant should produce full energy when deuterium storage is above 0, regardless of production rate');

        // Test with positive deuterium production but no deuterium in storage
        $this->planetSetObjectLevel('deuterium_synthesizer', 30);
        $this->planetSetObjectLevel('fusion_plant', 20);
        $this->planetAddResources(new Resources(0, 0, -500, 0)); // Remove remaining deuterium

        $noStorageEnergy = $this->planetService->energyProduction()->get();
        $this->assertGreaterThan(0, $noStorageEnergy, 'Fusion plant should produce energy when deuterium production is positive but deuterium storage is 0');

        // Test with negative deuterium production and no deuterium in storage
        $this->planetSetObjectLevel('deuterium_synthesizer', 10);
        $this->planetSetObjectLevel('fusion_plant', 20);
        // deuterium storage already at 0

        $noStorageEnergy = $this->planetService->energyProduction()->get();
        $this->assertEquals(0, $noStorageEnergy, 'Fusion plant should produce no energy when deuterium storage is 0');
    }

    /**
     * Test Planet Position and Plasma Technology bonus application to both mine and planet position bonus
     * Note: Plasma Technology does not apply to basic income, but Planet Slot does apply to basic income
     */
    public function testPlanetSlotAndPlasmaProductionBonus(): void
    {
        $this->playerSetResearchLevel('plasma_technology', 12);

        // base values breakdown (8x speed)
        // basic: metal = 30 * 8 = 240, crystal = 15 * 8 = 120, deuterium = 0
        // metal mine lv 20 = 30 * 8 * 20 * 1.1 ** 20 = 32_292
        // crystal mine lv 20 = 20 * 8 * 20 * 1.1 ** 20 = 21_528
        // deuterium mine lv 20 = 8 * 10 * 20 * 1.1 ** 20 * (1.44 - 0.004 * 47) = 13_477
        //      planet avg temp = (27 + 67) / 2 = 47

        // +35% metal production (basic + mine), +12% plasma tech
        $this->planetSetObjectLevel('metal_mine', 20);
        $this->planetSetObjectLevel('crystal_mine', 20);
        $this->planetSetObjectLevel('deuterium_synthesizer', 20);
        $this->planetSetObjectLevel('solar_plant', 50); // ensures 100% production factor

        // Get current planet coordinates
        $currentCoords = $this->planetService->getPlanetCoordinates();

        // Remove any existing planet at position 8 to avoid UNIQUE constraint violation
        \DB::table('planets')
            ->where('galaxy', $currentCoords->galaxy)
            ->where('system', $currentCoords->system)
            ->where('planet', 8)
            ->where('planet_type', $this->planetService->getPlanetType()->value)
            ->where('id', '!=', $this->currentPlanetId)
            ->delete();

        // Set planet position and temperature
        \DB::table('planets')->where('id', $this->currentPlanetId)->update([
            'planet' => 8,
            'temp_min' => 27,
            'temp_max' => 67,
        ]);

        // Reload and recalculate production after updating position
        $this->planetReloadAndRecalculateProduction();

        $this->assertEquals(49_149, $this->planetService->getMetalProductionPerHour());
        $this->assertEquals(23_353, $this->planetService->getCrystalProductionPerHour());
        $this->assertEquals(14_010, $this->planetService->getDeuteriumProductionPerHour());

        // +40% crystal production, +7.92% (1+0.0066*12) plasma tech
        $this->planetSetObjectLevel('metal_mine', 20);
        $this->planetSetObjectLevel('crystal_mine', 20);
        $this->planetSetObjectLevel('deuterium_synthesizer', 20);
        $this->planetSetObjectLevel('solar_plant', 50); // ensures 100% production factor

        // Remove any existing planet at position 1 to avoid UNIQUE constraint violation
        \DB::table('planets')
            ->where('galaxy', $currentCoords->galaxy)
            ->where('system', $currentCoords->system)
            ->where('planet', 1)
            ->where('planet_type', $this->planetService->getPlanetType()->value)
            ->where('id', '!=', $this->currentPlanetId)
            ->delete();

        // Set planet position and temperature
        \DB::table('planets')->where('id', $this->currentPlanetId)->update([
            'planet' => 1,
            'temp_min' => 27,
            'temp_max' => 67,
        ]);

        // Reload and recalculate production after updating position
        $this->planetReloadAndRecalculateProduction();

        $this->assertEquals(36_407, $this->planetService->getMetalProductionPerHour());
        $this->assertEquals(32_694, $this->planetService->getCrystalProductionPerHour());
        $this->assertEquals(14_010, $this->planetService->getDeuteriumProductionPerHour());
    }
}
