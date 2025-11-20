<?php

namespace Tests\Unit;

use OGame\Models\Resources;
use OGame\Services\ObjectService;
use Tests\AccountTestCase;

class MissileSiloCapacityTest extends AccountTestCase
{
    public function testCannotBuildIPMWhenSiloIsFull(): void
    {
        $this->planetAddResources(new Resources(1000000, 1000000, 1000000, 0));
        $this->planetSetObjectLevel('missile_silo', 2);
        $this->planetAddUnit('interplanetary_missile', 10); // CHANGED: 2 levels * 10 slots = 20 slots, 10 IPM * 2 = 20 slots (FULL)

        $maxBuildable = ObjectService::getObjectMaxBuildAmount('interplanetary_missile', $this->planetService, true);

        $this->assertEquals(0, $maxBuildable);
    }

    public function testCannotBuildABMWhenSiloIsFull(): void
    {
        $this->planetAddResources(new Resources(1000000, 1000000, 1000000, 0));
        $this->planetSetObjectLevel('missile_silo', 1);
        $this->planetAddUnit('anti_ballistic_missile', 10); // CHANGED: 1 level * 10 slots = 10 slots (FULL)

        $maxBuildable = ObjectService::getObjectMaxBuildAmount('anti_ballistic_missile', $this->planetService, true);

        $this->assertEquals(0, $maxBuildable);
    }

    public function testMixedIPMAndABMCapacity(): void
    {
        $this->planetAddResources(new Resources(1000000, 1000000, 1000000, 0));
        $this->planetSetObjectLevel('missile_silo', 2);
        $this->planetAddUnit('interplanetary_missile', 5); // CHANGED: 5 IPM * 2 = 10 slots
        $this->planetAddUnit('anti_ballistic_missile', 10); // CHANGED: 10 ABM * 1 = 10 slots (total = 20, FULL)

        $maxIPM = ObjectService::getObjectMaxBuildAmount('interplanetary_missile', $this->planetService, true);
        $maxABM = ObjectService::getObjectMaxBuildAmount('anti_ballistic_missile', $this->planetService, true);

        $this->assertEquals(0, $maxIPM);
        $this->assertEquals(0, $maxABM);
    }

    public function testCanBuildWhenSiloHasSpace(): void
    {
        $this->planetAddResources(new Resources(1000000, 1000000, 1000000, 0));
        $this->planetSetObjectLevel('missile_silo', 2);
        $this->planetAddUnit('interplanetary_missile', 2); // CHANGED: 2 IPM * 2 = 4 slots, 16 slots remaining

        $maxIPM = ObjectService::getObjectMaxBuildAmount('interplanetary_missile', $this->planetService, true);

        $this->assertGreaterThan(0, $maxIPM);
    }

    public function testNoSiloMeansNoMissiles(): void
    {
        $this->planetAddResources(new Resources(1000000, 1000000, 1000000, 0));
        $this->planetSetObjectLevel('missile_silo', 0);

        $maxIPM = ObjectService::getObjectMaxBuildAmount('interplanetary_missile', $this->planetService, true);
        $maxABM = ObjectService::getObjectMaxBuildAmount('anti_ballistic_missile', $this->planetService, true);

        $this->assertEquals(0, $maxIPM);
        $this->assertEquals(0, $maxABM);
    }
}
