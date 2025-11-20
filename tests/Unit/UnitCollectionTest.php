<?php

namespace Tests\Unit;

use Exception;
use OGame\GameObjects\Models\Units\UnitCollection;
use OGame\Services\ObjectService;
use Tests\AccountTestCase;

/**
 * Class UnitCollectionTest
 * @package Tests\Unit
 *
 * Test class for unit collections.
 */
class UnitCollectionTest extends AccountTestCase
{
    /**
     * Test that the slowest unit speed is calculated correctly.
     * @throws Exception
     */
    public function testSlowestFleetSpeed(): void
    {
        $this->planetAddUnit('small_cargo', 10);
        $this->planetAddUnit('destroyer', 3);
        $this->planetAddUnit('espionage_probe', 2);

        $this->playerSetResearchLevel('hyperspace_drive', 1);
        $this->playerSetResearchLevel('combustion_drive', 20); // This will make small cargo faster than destroyer.

        $unitCollection = new UnitCollection();
        $unitCollection->addUnit(ObjectService::getShipObjectByMachineName('small_cargo'), 10);
        $unitCollection->addUnit(ObjectService::getShipObjectByMachineName('destroyer'), 3);

        // Slowest ship should be the destroyer.
        // - 5.000 = destroyer base speed
        // - 1.500 = 30% speed bonus from hyperspace drive level 1
        // =  6.500 total expected speed.
        $this->assertEquals(6500, $unitCollection->getSlowestUnitSpeed($this->planetService->getPlayer()));
    }
}
