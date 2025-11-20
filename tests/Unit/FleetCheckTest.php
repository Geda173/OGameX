<?php

namespace Tests\Unit;

use Tests\AccountTestCase;

class FleetCheckTest extends AccountTestCase
{
    /**
     * Mock test for checking positive fleet amount check on a planet.
     */
    public function testFleetAmountCheckPositive(): void
    {
        $this->planetAddUnit('small_cargo', 10);
        $this->planetAddUnit('destroyer', 3);
        $this->planetAddUnit('espionage_probe', 2);

        // Verify that multiple ships count up to the sum of the ships.
        $this->assertEquals(15, $this->planetService->getFlightShipAmount());
    }

    /**
     * Mock test for checking zero fleet amount check on a planet.
     */
    public function testFleetAmountCheckZero(): void
    {
        $this->planetAddUnit('solar_satellite', 3);

        // Verify that amount of ships returns 0 as there are no ships that can fly.
        $this->assertEquals(0, $this->planetService->getFlightShipAmount());
    }
}
