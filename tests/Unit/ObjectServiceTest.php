<?php

namespace Tests\Unit;

use OGame\Models\Resources;
use OGame\Services\ObjectService;
use Tests\AccountTestCase;

class ObjectServiceTest extends AccountTestCase
{
    /**
     * Tests maximum building amount returns correct value.
     */
    public function testGetObjectMaxBuildAmount(): void
    {
        // Test with requirements not met
        $max_build_amount = ObjectService::getObjectMaxBuildAmount('plasma_turret', $this->planetService, false);
        $this->assertEquals(0, $max_build_amount);

        // Test with object limited to one instance per user
        $max_build_amount = ObjectService::getObjectMaxBuildAmount('small_shield_dome', $this->planetService, true);
        $this->assertEquals(1, $max_build_amount);

        $this->planetAddUnit('small_shield_dome', 1);

        // Test with object limited to one instance which already exists
        $maxBuildAmount = ObjectService::getObjectMaxBuildAmount('small_shield_dome', $this->planetService, true);
        $this->assertEquals(0, $maxBuildAmount);

        $this->planetAddResources(new Resources(24000, 6000, 0, 0));
        $this->planetSetObjectLevel('missile_silo', 1);

        // Test it calculates max amount correctly
        $maxBuildAmount = ObjectService::getObjectMaxBuildAmount('anti_ballistic_missile', $this->planetService, true);
        $this->assertEquals(3, $maxBuildAmount);
    }
}
