<?php

namespace Tests\Feature\FleetDispatch;

use Illuminate\Support\Facades\Date;
use OGame\GameObjects\Models\Units\UnitCollection;
use OGame\Models\Enums\PlanetType;
use OGame\Models\FleetMission;
use OGame\Models\Resources;
use OGame\Services\FleetMissionService;
use OGame\Services\ObjectService;
use OGame\Services\SettingsService;
use Tests\FleetDispatchTestCase;

/**
 * Regression tests for the battle engine rapidfire performance bug.
 *
 * Bug: When millions of defending ships had rapidfire against 1-2 attacker units,
 * the Rust battle engine would loop billions of times shooting at already-dead targets
 * (dead units were not removed from the Vec until cleanup_round() ran after all combat).
 * This caused PHP-FPM to timeout, the attack mission was never marked processed=1,
 * and every subsequent page load re-triggered the same computation — locking the account.
 *
 * Fix: In process_combat(), skip targets whose current_hull_plating <= 0.
 */
class FleetDispatchBattleRapidfireTest extends FleetDispatchTestCase
{
    protected int $missionType = 1;

    protected string $missionName = 'Attack';

    protected function basicSetup(): void
    {
        $this->planetAddUnit('espionage_probe', 1);
        $this->playerSetResearchLevel('computer_technology', 1);

        $settingsService = resolve(SettingsService::class);
        $settingsService->set('economy_speed', 8);
        $settingsService->set('fleet_speed_war', 1);
        $settingsService->set('fleet_speed_holding', 1);
        $settingsService->set('fleet_speed_peaceful', 1);

        $this->planetAddResources(new Resources(0, 0, 1000000, 0));
    }

    protected function messageCheckMissionArrival(): void
    {
    }

    protected function messageCheckMissionReturn(): void
    {
    }

    /**
     * Regression test: battle engine must complete in finite time when millions of defenders
     * have high rapidfire against 1-2 attacker units.
     *
     * Scenario: 1 million Deathstars (rapidfire=250 vs espionage probes) defend against
     * 1 espionage probe. Deathstars have the highest rapidfire against probes (250), making
     * this the worst-case scenario for the dead-target rapidfire loop.
     *
     * Without the fix: once the probe dies, the rapidfire continuation loop keeps firing at
     * it 250 times per Deathstar → up to 999,999 × 250 ≈ 250M wasted iterations per round.
     * With the fix: dead targets (current_hull_plating <= 0) are skipped immediately in
     * process_combat(), so the battle completes in well under a second.
     *
     * The real-world crash fleet also contained 27M light fighters, 725k heavy fighters,
     * 4M cruisers, 3M battleships, 55M battlecruisers, 72k bombers, 1M destroyers, 11M
     * reapers and 262k pathfinders. That volume causes a separate O(n) expand_fleets()
     * bottleneck unrelated to the rapidfire bug; the 1M deathstar scenario is sufficient
     * to isolate and validate the dead-target guard.
     */
    public function testBattleCompletesWithMassiveRapidfireAgainstSingleTarget(): void
    {
        $this->basicSetup();

        // Arm the target planet with 1 million Deathstars — the ship with the highest
        // rapidfire against espionage probes (250), the worst-case for the dead-target loop.
        $targetPlanet = $this->getNearbyForeignPlanet();
        $targetPlanet->addUnit('deathstar', 1000000);

        // Attack with a single espionage probe — 1 attacker unit vs 1M deathstars.
        $attackFleet = new UnitCollection();
        $attackFleet->addUnit(ObjectService::getUnitObjectByMachineName('espionage_probe'), 1);
        $this->dispatchFleet(
            $targetPlanet->getPlanetCoordinates(),
            $attackFleet,
            new Resources(0, 0, 0, 0),
            PlanetType::Planet
        );

        $fleetMissionService = resolve(FleetMissionService::class, ['player' => $this->planetService->getPlayer()]);
        $attackMission = $fleetMissionService->getActiveFleetMissionsForCurrentPlayer()->first();
        $this->assertNotNull($attackMission, 'Attack mission should have been created');

        // Advance time to after arrival.
        $this->travelTo(Date::createFromTimestamp($attackMission->time_arrival + 1));
        $this->reloadApplication();

        // Trigger mission processing and measure wall-clock time.
        // NOTE: Do NOT call playerSetAllMessagesRead() before this block — it hits
        // /ajax/messages which calls updateFleetMissions() and would process the battle
        // before we start the clock, making $elapsed measure only the page render.
        $start = microtime(true);
        $this->get('/overview');
        $elapsed = microtime(true) - $start;

        $processedMission = FleetMission::find($attackMission->id);
        $this->assertEquals(
            1,
            $processedMission->processed,
            'Attack mission must be marked processed after battle. ' .
            'If this fails the battle engine timed out on the rapidfire loop.'
        );

        // 5 seconds is the performance threshold.
        // With the fix:    dead targets are skipped immediately → completes in ~1s.
        // Without the fix: 1M deathstars × rapidfire=250 against 1 dead probe
        //                  = ~250M wasted iterations per round → >> 5s.
        $this->assertLessThan(
            5.0,
            $elapsed,
            sprintf(
                'Battle engine took %.2fs — exceeded 5s threshold. ' .
                'The dead-target rapidfire guard in process_combat() may be missing. ' .
                'Without the fix 1M deathstars × rapidfire=250 against 1 probe = 250M wasted iterations.',
                $elapsed
            )
        );
    }
}
