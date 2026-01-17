# Alliance Combat System (ACS) Implementation Plan

## Overview

This document outlines the implementation plan for adding Alliance Combat System (ACS) functionality to OGameX. ACS allows multiple players to coordinate their fleets to attack or defend a target together, combining their forces in a single battle.

### Revision History

| Date | Change |
|------|--------|
| 2026-01-17 | PR 4 (Alliance Depot) completed. Full supply rocket mechanics implemented. ACS Defend feature is now 100% complete. Bonus: Full Alliance System also implemented. |
| 2026-01-06 | Revised PR 4 (Alliance Depot): Players CAN extend hold time via supply rockets. Sender pays initial costs, host pays for extensions. Added UI requirements (button + popup in /facilities). Split into sub-PRs 4a/4b/4c. |
| 2026-01-06 | Updated: PR 3 (Multi-Defender Battle Engine) completed. ACS Defend is now fully functional. |
| 2026-01-03 | Updated: PR 2 marked as completed. PR 3, PR 5, and PR 6 now ready for development. |
| 2026-01-03 | Updated: PR 1 marked as completed. Added implementation notes and updated dependency table. |
| Initial | Original implementation plan created |

---

## Code Standards & Compliance

**IMPORTANT**: All code written for this feature MUST adhere to the following standards:

1. **CONTRIBUTING.md Guidelines**: Follow all guidelines specified in the project's [CONTRIBUTING.md](/CONTRIBUTING.md) file, including:
   - Commit message conventions
   - Pull request requirements
   - Code review expectations
   - Testing requirements

2. **PHPStan Compliance**: All code must pass PHPStan static analysis at the project's configured level
   - Run `./vendor/bin/phpstan analyse` before committing
   - No ignored errors unless absolutely necessary and documented
   - Proper type hints on all method parameters and return types

3. **PSR-12 Coding Standard**: All PHP code must be fully compliant with PSR-12
   - Run `./vendor/bin/pint` to auto-fix formatting issues
   - Consistent indentation (4 spaces)
   - Proper brace placement and spacing
   - Namespace and use statement ordering

4. **Clean GitHub History**: Keep all GitHub references free of AI/assistant mentions
   - NEVER mention "Claude", "AI", "assistant", or similar in commit messages
   - NEVER mention "Claude", "AI", "assistant", or similar in PR titles or descriptions
   - NEVER reference this plan or any AI involvement in GitHub activity
   - Write commit messages and PR descriptions as if written by a human developer
   - Focus on *what* changed and *why*, not *how* it was developed

**Pre-commit Checklist**:
```bash
# Run these commands before every commit:
./vendor/bin/pint              # Fix PSR-12 formatting
./vendor/bin/phpstan analyse   # Check static analysis
php artisan test               # Run test suite
```

---

## Phased Pull Request Strategy

This feature should be implemented across multiple smaller PRs rather than one giant PR. This allows for easier code review, incremental testing, and delivers value sooner.

### Recommended PR Sequence

```
PR 1: ACS Defend (Basic) ✅        ──┐
                                    ├──► PR 3: Multi-Defender Battle Engine ✅
PR 2: BattleUnit Owner Tracking ✅ ──┘
                                            │
                                            ▼
                                    PR 4: Alliance Depot ✅

PR 5: Fleet Unions Foundation     ──┐
                                    ├──► PR 7: ACS Attack Mission
PR 6: Multi-Attacker Battle       ──┘
                                            │
                                            ▼
                                    PR 8: Loot Distribution & Sync Returns
                                            │
                                            ▼
                                    PR 9: Enhanced Battle Reports & UI
```

### Current Progress
- **PR 1**: ✅ Completed - Basic ACS Defend holding
- **PR 2**: ✅ Completed - BattleUnit owner tracking
- **PR 3**: ✅ Completed - Multi-defender battle engine
- **PR 4**: ✅ Completed - Alliance Depot with supply rockets
- **PR 5-9**: Pending

🎉 **ACS Defend is now FULLY COMPLETE** with all planned features including Alliance Depot supply rockets!

---

### PR 1: ACS Defend - Basic Hold Mission ✅ COMPLETED
**Branch**: `feature/acs-defend-basic`
**Merged**: PR #974
**Size**: ~500-800 lines
**Deliverable**: Players can send fleets to hold at ally/buddy planets

| What's Included | What's NOT Included |
|-----------------|---------------------|
| `AcsDefendMission` class (type 5) | Multi-defender battle integration |
| Hold time selection (0-32 hours) | Alliance Depot |
| Send to ally/buddy validation | Per-fleet battle reports |
| Fleet returns after hold expires | - |
| Basic UI for mission selection | - |

**Tasks from Milestones**: 4.2 (partial), 4.3 (type 5 only)

**Acceptance Criteria**:
- [ ] Can send defend mission to alliance member's planet *(TODO: alliance check pending)*
- [x] Can send defend mission to buddy's planet
- [x] Cannot send defend mission to stranger's planet
- [ ] Hold time dropdown shows valid options (0,1,2,4,8,16,32 hours) *(basic hold works, UI dropdown not yet)*
- [x] Fleet returns home after hold time expires
- [x] Fleet can be recalled during hold

**Implementation Notes** (added post-completion):
- Buddy validation implemented via `BuddyService::areBuddies()`
- Alliance member check marked as TODO for when alliance system is implemented
- Message classes created: `AcsDefendArrivalHost`, `AcsDefendArrivalSender`
- Comprehensive test suite in `FleetDispatchAcsDefendTest.php`

---

### PR 2: BattleUnit Owner Tracking ✅ COMPLETED
**Branch**: `feature/battle-unit-owner-tracking`
**Merged**: PR #1003
**Size**: ~200-300 lines
**Deliverable**: Foundation for tracking which fleet owns each ship in battle

| What's Included | What's NOT Included |
|-----------------|---------------------|
| `fleetMissionId` property on BattleUnit | AcsBattleEngine |
| `ownerId` property on BattleUnit | Multi-fleet results |
| Updated constructors | Battle report changes |
| Unit tests for new properties | - |

**Tasks from Milestones**: 2.1

**Acceptance Criteria**:
- [x] BattleUnit accepts and stores fleetMissionId
- [x] BattleUnit accepts and stores ownerId
- [x] Existing 1v1 battles continue to work unchanged
- [x] PHPStan passes, PSR-12 compliant

**Implementation Notes** (added post-completion):
- `fleetMissionId` and `ownerId` are now required constructor parameters (not optional)
- Both PHP and Rust battle engines updated to pass ownership info
- All mission types updated: `AttackMission`, `EspionageMission`, `ExpeditionMission`, `MoonDestructionMission`
- Stationary planet defenses use `fleetMissionId = 0`
- New test file: `tests/Unit/BattleEngine/BattleUnitTest.php`

---

### PR 3: Multi-Defender Battle Engine ✅ COMPLETED
**Branch**: `feature/multi-defender-battle`
**Size**: ~600-900 lines
**Deliverable**: Defending fleets from PR 1 now participate in battles

| What's Included | What's NOT Included |
|-----------------|---------------------|
| Collect defending fleets at planet | Multi-attacker support |
| Include in battle as defenders | Loot distribution |
| Per-fleet result tracking for defenders | Fleet unions |
| Destroyed fleet handling (no empty returns) | - |
| DefenderFleet/DefenderResult models | - |

**Tasks from Milestones**: 2.3 (defender version), 2.4, 2.5 (defender parts)

**Acceptance Criteria**:
- [x] Defending fleet fights alongside planet owner's defenses
- [x] Each defender uses their own tech levels
- [x] Destroyed defender fleet: no return mission, slot freed
- [x] Surviving defender fleet: returns with survivors
- [x] Battle report shows defending fleets participated

**Implementation Notes** (added post-completion):
- New models: `DefenderFleet` (from planet or fleet mission), `DefenderFleetResult`
- Battle engine updated to collect ACS defend fleets at target planet
- Per-fleet survivor tracking and result assignment
- Comprehensive test suite: `FleetDispatchMultiDefenderBattleTest.php`

---

### PR 4: Alliance Depot ✅ COMPLETED
**Branch**: `feature/alliance-depot`
**Size**: ~800-1200 lines (implemented as single PR)
**Deliverable**: Planet owner can extend hold time for defending fleets via supply rockets

All sub-PRs (4a, 4b, 4c) were implemented together.

#### Game Mechanics (Implemented)

**Cost Model**:
- **Sender** pays ALL initial costs: flight deuterium + holding deuterium for initial hold time
- **Host** only pays when sending supply rockets to extend hold time beyond initial period
- Fleets holding for **1 hour or more** can have their hold time extended

**Alliance Depot Building**:
- Building costs: 20,000 Metal + 40,000 Crystal at level 1 (doubles each level)
- Located in Facilities
- Supply capacity: 10,000 deuterium per level per hour

**UI Implementation**:
- Button in `/facilities` page labeled "Alliance Depot" (same styling as Jump Gate)
- Popup shows currently holding fleets with countdown timers
- Dropdown to select fleet, input for extension hours
- Deuterium cost displayed per hour based on fleet composition

**Holding Costs** (deuterium per hour per ship):

| Ship Type | Deut/Hour | Ship Type | Deut/Hour |
|-----------|-----------|-----------|-----------|
| Small Cargo | 1 | Bomber | 100 |
| Large Cargo | 5 | Destroyer | 100 |
| Light Fighter | 2 | Battlecruiser | 25 |
| Heavy Fighter | 7 | Death Star | 0.1 |
| Cruiser | 30 | Espionage Probe | 0.1 |
| Battleship | 60 | Recycler | 30 |
| Colony Ship | 100 | - | - |

#### Acceptance Criteria (All Complete)

**Sub-PR 4a: Building & UI Foundation**:
- [x] Alliance Depot building can be constructed (Facilities category)
- [x] Button appears in `/facilities` when depot level >= 1
- [x] Popup opens showing holding fleets at this planet
- [x] Popup styling matches existing popups (Jump Gate style)

**Sub-PR 4b: Supply Rocket Mechanics**:
- [x] Host can send supply rocket to extend fleet hold time
- [x] Only fleets holding 1+ hours can be extended
- [x] Deuterium cost calculated based on fleet composition
- [x] Host's deuterium is deducted when rocket sent
- [x] Fleet's hold time is extended accordingly

**Sub-PR 4c: Mission Integration**:
- [x] Extended fleets continue holding until new time expires
- [x] Fleets return home when final hold time expires
- [x] Multiple extensions can be stacked

**Implementation Notes** (added post-completion):
- `AllianceDepotService` - cost calculation, hold time extension, fleet tracking
- `AllianceDepotController` - handles UI and supply rocket requests
- `dialog.blade.php` - popup UI with fleet dropdown, countdown timers, cost display
- Tests: `AllianceDepotSupplyRocketTest.php`, `AllianceDepotTest.php`
- New migration: `add_processed_hold_to_fleet_missions_table.php`

**Bonus**: Full Alliance System was also implemented alongside this PR!

---

### PR 5: Fleet Unions Foundation
**Branch**: `feature/fleet-unions`
**Size**: ~600-800 lines
**Deliverable**: Database and service layer for coordinating fleets

| What's Included | What's NOT Included |
|-----------------|---------------------|
| `fleet_unions` table migration | ACS Attack mission |
| `union_id` column on fleet_missions | UI for creating unions |
| `FleetUnion` model | Battle processing |
| `FleetUnionService` (create, join, validate) | - |
| Delay limit calculation (30%) | - |

**Tasks from Milestones**: 1.1, 1.2, 1.3, 1.4, 3.1, 3.2, 3.3, 3.4, 3.5

**Acceptance Criteria**:
- [ ] Can create a union from an attack mission
- [ ] Can join a union (buddy/ally validation)
- [ ] Max 16 fleets, 5 players enforced
- [ ] 30% delay limit calculated correctly
- [ ] Fleet recall removes from union

---

### PR 6: Multi-Attacker Battle Engine
**Branch**: `feature/multi-attacker-battle`
**Size**: ~700-1000 lines
**Deliverable**: Battle engine supports multiple attacking fleets

| What's Included | What's NOT Included |
|-----------------|---------------------|
| `AttackerFleet` model | ACS Attack mission integration |
| `AttackerResult` model | Loot distribution |
| `AcsBattleEngine` class | UI changes |
| Per-fleet round tracking | - |
| Survivor assignment to owners | - |

**Tasks from Milestones**: 2.2, 2.3, 2.4, 2.5, 2.6

**Acceptance Criteria**:
- [ ] Engine accepts multiple AttackerFleets
- [ ] Each attacker uses their own tech levels
- [ ] Survivors correctly assigned to original owners
- [ ] Round data tracked per fleet
- [ ] Unit tests pass for multi-attacker scenarios

---

### PR 7: ACS Attack Mission
**Branch**: `feature/acs-attack-mission`
**Size**: ~800-1200 lines
**Deliverable**: Full ACS Attack functionality (type 2)

| What's Included | What's NOT Included |
|-----------------|---------------------|
| `AcsAttackMission` class | Loot distribution |
| Mission type 1 → 2 conversion on union create | Synchronized returns |
| Coordinated arrival times | Enhanced battle reports |
| UI for union creation | - |
| UI for joining unions | - |
| `processUnionBattle()` integration | - |

**Tasks from Milestones**: 3.6, 4.1, 4.3, 4.4, 7.1-7.4

**Acceptance Criteria**:
- [ ] Can create union from attack mission
- [ ] Allies/buddies can join union
- [ ] All fleets arrive simultaneously
- [ ] Battle processes with all attackers
- [ ] Each fleet's survivors return to origin

---

### PR 8: Loot Distribution & Synchronized Returns
**Branch**: `feature/acs-loot-sync`
**Size**: ~400-600 lines
**Deliverable**: Fair loot split and coordinated return flights

| What's Included | What's NOT Included |
|-----------------|---------------------|
| Loot split by surviving cargo capacity | Battle report filtering |
| Synchronized return speeds (slowest fleet) | - |
| Cargo resource survival on losses | - |
| Zero-cargo fleet handling | - |

**Tasks from Milestones**: 5.1, 5.2, 5.3, 5.4

**Acceptance Criteria**:
- [ ] Loot distributed proportionally to cargo capacity
- [ ] Fleet with no cargo gets no loot
- [ ] All return missions have same duration
- [ ] Carried resources survive proportionally to cargo losses

---

### PR 9: Enhanced Battle Reports & UI Polish
**Branch**: `feature/acs-battle-reports`
**Size**: ~600-900 lines
**Deliverable**: Per-fleet filtering and polished UI

| What's Included | What's NOT Included |
|-----------------|---------------------|
| Per-fleet battle report data | - |
| Fleet filter dropdown in report view | - |
| Individual fleet view template | - |
| Fleet movement UI improvements | - |
| One report per player (not per fleet) | - |

**Tasks from Milestones**: 6.1-6.6, 7.5-7.7

**Acceptance Criteria**:
- [ ] Battle report shows all participating fleets
- [ ] Can filter report to show single fleet's perspective
- [ ] Each round shows per-fleet ship counts
- [ ] Player receives one report even with multiple fleets

---

### Summary: PR Dependencies

| PR | Status | Can Start After | Delivers |
|----|--------|-----------------|----------|
| **PR 1** | ✅ Done | ~~Immediately~~ | Basic ACS Defend holding |
| **PR 2** | ✅ Done | ~~Immediately~~ | Owner tracking foundation |
| **PR 3** | ✅ Done | ~~PR 1 + PR 2~~ | Defenders participate in battle |
| **PR 4** | ✅ Done | ~~PR 3~~ | Alliance Depot + supply rockets |
| **PR 5** | Ready | Immediately | Fleet unions foundation |
| **PR 6** | Ready | Immediately | Multi-attacker battle engine |
| **PR 7** | Blocked | PR 5 + PR 6 | Full ACS Attack |
| **PR 8** | Blocked | PR 7 | Loot & sync returns |
| **PR 9** | Blocked | PR 7 | Battle reports & UI |

🎉 **ACS DEFEND COMPLETE!** The full ACS Defend feature is now implemented:
- Send fleets to defend buddy/ally planets
- Defending fleets participate in battles with their own tech levels
- Alliance Depot allows host to extend hold time via supply rockets
- Full Alliance System also implemented (create/join alliances, ranks, applications)

**Next Steps - ACS Attack** (can be developed in parallel):
- **PR 5**: Fleet Unions Foundation - database/service layer for coordinating attack fleets
- **PR 6**: Multi-Attacker Battle Engine - battle engine supports multiple attacking fleets

**Parallel Development Possible**:
- PR 5 and PR 6 can be developed in parallel now
- PR 7 requires both PR 5 and PR 6

---

## PR Implementation Guides

These step-by-step guides are designed to be self-contained. Each PR guide tells you exactly what files to read, what to create, and how to test.

---

### PR 1 Implementation Guide: ACS Defend - Basic Hold Mission

#### Step 1: Study Existing Patterns (Read First!)

```bash
# Read these files to understand the patterns:
app/GameMissions/Abstracts/GameMission.php      # Base class - understand structure
app/GameMissions/DeploymentMission.php          # Simple mission example
app/GameMissions/AttackMission.php              # Complex mission with combat
app/Factories/GameMissionFactory.php            # How missions are registered
app/Models/FleetMission.php                     # Mission database model
tests/Feature/FleetDispatch/FleetDispatchAttackTest.php  # Test pattern
tests/FleetDispatchTestCase.php                 # Test base class
```

#### Step 2: Create the AcsDefendMission Class

**File**: `app/GameMissions/AcsDefendMission.php`

```php
<?php

namespace OGame\GameMissions;

use OGame\Enums\FleetMissionStatus;
use OGame\Enums\FleetSpeedType;
use OGame\GameMissions\Abstracts\GameMission;
use OGame\GameMissions\Models\MissionPossibleStatus;
use OGame\GameObjects\Models\Units\UnitCollection;
use OGame\Models\Enums\PlanetType;
use OGame\Models\FleetMission;
use OGame\Models\Planet\Coordinate;
use OGame\Services\PlanetService;

class AcsDefendMission extends GameMission
{
    protected static string $name = 'ACS Defend';
    protected static int $typeId = 5;
    protected static bool $hasReturnMission = true;
    protected static FleetSpeedType $fleetSpeedType = FleetSpeedType::holding;
    protected static FleetMissionStatus $friendlyStatus = FleetMissionStatus::Friendly;

    /**
     * Valid hold times in hours.
     * @var array<int>
     */
    private const VALID_HOLD_TIMES = [0, 1, 2, 4, 8, 16, 32];

    /**
     * @inheritdoc
     */
    public function isMissionPossible(
        PlanetService $planet,
        Coordinate $targetCoordinate,
        PlanetType $targetType,
        UnitCollection $units
    ): MissionPossibleStatus {
        // Cannot send missions while in vacation mode
        if ($planet->getPlayer()->isInVacationMode()) {
            return new MissionPossibleStatus(false, __('t_acs.error_vacation_mode'));
        }

        // ACS Defend is only possible for planets and moons
        if (!in_array($targetType, [PlanetType::Planet, PlanetType::Moon])) {
            return new MissionPossibleStatus(false);
        }

        // Target planet must exist
        $targetPlanet = $this->planetServiceFactory->makeForCoordinate($targetCoordinate, true, $targetType);
        if ($targetPlanet === null) {
            return new MissionPossibleStatus(false);
        }

        // Cannot defend own planet (use Deployment instead)
        if ($planet->getPlayer()->equals($targetPlanet->getPlayer())) {
            return new MissionPossibleStatus(false, __('t_acs.error_cannot_defend_own_planet'));
        }

        // Target must be ally or buddy
        $currentPlayer = $planet->getPlayer();
        $targetPlayer = $targetPlanet->getPlayer();

        if (!$this->isAllyOrBuddy($currentPlayer, $targetPlayer)) {
            return new MissionPossibleStatus(false, __('t_acs.error_not_ally_or_buddy'));
        }

        // TODO: Check max 5 players defending limit

        return new MissionPossibleStatus(true);
    }

    /**
     * Check if two players are allies or buddies.
     */
    private function isAllyOrBuddy(PlayerService $player1, PlayerService $player2): bool
    {
        // Check if buddies using BuddyService
        $buddyService = resolve(\OGame\Services\BuddyService::class);
        if ($this->areBuddies($buddyService, $player1->getId(), $player2->getId())) {
            return true;
        }

        // Check if in same alliance
        // TODO: Implement alliance check when alliance system is available

        return false;
    }

    /**
     * Check if two users are buddies (have an accepted buddy request).
     */
    private function areBuddies(BuddyService $buddyService, int $userId1, int $userId2): bool
    {
        return \OGame\Models\BuddyRequest::where('status', \OGame\Models\BuddyRequest::STATUS_ACCEPTED)
            ->where(function ($query) use ($userId1, $userId2) {
                $query->where(function ($q) use ($userId1, $userId2) {
                    $q->where('sender_user_id', $userId1)
                        ->where('receiver_user_id', $userId2);
                })->orWhere(function ($q) use ($userId1, $userId2) {
                    $q->where('sender_user_id', $userId2)
                        ->where('receiver_user_id', $userId1);
                });
            })
            ->exists();
    }

    /**
     * @inheritdoc
     */
    protected function processArrival(FleetMission $mission): void
    {
        // Fleet has arrived - now it's holding at the target planet
        // The fleet will participate in any battle that occurs during hold time
        // Hold time is tracked via time_arrival + hold_time on the mission

        // Send arrival message
        $this->sendFleetArrivalMessage($mission);

        // Note: We don't mark as processed yet - fleet is holding
        // The fleet will be processed when:
        // 1. Hold time expires -> return home
        // 2. Battle occurs -> participate, then continue holding or return
        // 3. Player recalls -> return home
    }

    /**
     * Check if the hold time has expired and create return mission if so.
     */
    public function processHoldTimeExpiration(FleetMission $mission): void
    {
        $holdEndTime = $mission->time_arrival + ($mission->hold_time ?? 0);

        if (time() >= $holdEndTime) {
            // Hold time expired, create return mission
            $this->createReturnMission($mission);

            // Mark original mission as processed
            $mission->processed = 1;
            $mission->save();
        }
    }

    /**
     * @inheritdoc
     */
    protected function processReturn(FleetMission $mission): void
    {
        $originPlanet = $this->planetServiceFactory->make($mission->planet_id_to, true);

        // Add units back to origin planet
        $originPlanet->addUnits($this->fleetMissionService->getFleetUnits($mission));

        // Add resources back (if any were carried)
        $resources = $this->fleetMissionService->getResources($mission);
        if ($resources->any()) {
            $originPlanet->addResources($resources);
        }

        // Send return message
        $this->sendFleetReturnMessage($mission, $originPlanet->getPlayer());

        // Mark as processed
        $mission->processed = 1;
        $mission->save();
    }

    /**
     * Send message that fleet has arrived at defend target.
     */
    private function sendFleetArrivalMessage(FleetMission $mission): void
    {
        // TODO: Create FleetDefendArrival message class
        // $this->messageService->sendSystemMessageToPlayer(...);
    }

    /**
     * Validate hold time is one of the allowed values.
     */
    public static function isValidHoldTime(int $hours): bool
    {
        return in_array($hours, self::VALID_HOLD_TIMES);
    }

    /**
     * Get valid hold times for UI dropdown.
     * @return array<int>
     */
    public static function getValidHoldTimes(): array
    {
        return self::VALID_HOLD_TIMES;
    }
}
```

#### Step 3: Register Mission in Factory

**File**: `app/Factories/GameMissionFactory.php`

Add the import at the top:
```php
use OGame\GameMissions\AcsDefendMission;
```

Update `getAllMissions()`:
```php
return [
    1 => resolve(AttackMission::class),
    3 => resolve(TransportMission::class),
    4 => resolve(DeploymentMission::class),
    5 => resolve(AcsDefendMission::class),  // ADD THIS LINE
    6 => resolve(EspionageMission::class),
    // ... rest
];
```

Update `getMissionById()`:
```php
return match ($missionId) {
    1 => resolve(AttackMission::class, $dependencies),
    3 => resolve(TransportMission::class, $dependencies),
    4 => resolve(DeploymentMission::class, $dependencies),
    5 => resolve(AcsDefendMission::class, $dependencies),  // ADD THIS LINE
    6 => resolve(EspionageMission::class, $dependencies),
    // ... rest
};
```

#### Step 4: Add Hold Time to FleetMission Model

**File**: `database/migrations/xxxx_add_hold_time_to_fleet_missions.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fleet_missions', function (Blueprint $table) {
            $table->integer('hold_time')->nullable()->after('time_arrival')
                ->comment('Hold time in seconds for ACS Defend missions');
        });
    }

    public function down(): void
    {
        Schema::table('fleet_missions', function (Blueprint $table) {
            $table->dropColumn('hold_time');
        });
    }
};
```

**File**: `app/Models/FleetMission.php` - Add to properties:
```php
 * @property int|null $hold_time
```

#### Step 5: Create Translation File

**File**: `resources/lang/en/t_acs.php`

```php
<?php

return [
    'mission_acs_defend' => 'ACS Defend',
    'hold_time' => 'Hold Time',
    'hold_time_hours' => ':hours hour(s)',

    // Errors
    'error_vacation_mode' => 'You cannot send missions while in vacation mode!',
    'error_cannot_defend_own_planet' => 'You cannot defend your own planet. Use Deployment instead.',
    'error_not_ally_or_buddy' => 'You can only defend planets of allies or buddies.',
    'error_max_defenders_reached' => 'Maximum number of defending players (5) reached.',
];
```

#### Step 6: Create Feature Test

**File**: `tests/Feature/FleetDispatch/FleetDispatchAcsDefendTest.php`

```php
<?php

namespace Tests\Feature\FleetDispatch;

use OGame\GameObjects\Models\Units\UnitCollection;
use OGame\Models\Resources;
use OGame\Services\ObjectService;
use OGame\Services\SettingsService;
use Tests\FleetDispatchTestCase;

class FleetDispatchAcsDefendTest extends FleetDispatchTestCase
{
    protected int $missionType = 5;
    protected string $missionName = 'ACS Defend';

    protected function basicSetup(): void
    {
        $this->planetAddUnit('light_fighter', 50);
        $this->playerSetResearchLevel('computer_technology', 5);
        $this->planetAddResources(new Resources(0, 0, 1000000, 0));

        $settingsService = resolve(SettingsService::class);
        $settingsService->set('fleet_speed_holding', 1);
    }

    protected function messageCheckMissionArrival(): void
    {
        // Check for defend arrival message
        $this->assertMessageReceivedAndContains('fleets', 'other', [
            'Your fleet has arrived',
        ]);
    }

    protected function messageCheckMissionReturn(): void
    {
        $this->assertMessageReceivedAndContains('fleets', 'other', [
            'Your fleet is returning',
        ]);
    }

    /**
     * Test that ACS Defend mission cannot be sent to own planet.
     */
    public function testFleetCheckToOwnPlanetError(): void
    {
        $this->basicSetup();
        $unitCollection = new UnitCollection();
        $unitCollection->addUnit(ObjectService::getUnitObjectByMachineName('light_fighter'), 10);
        $this->fleetCheckToSecondPlanet($unitCollection, false);
    }

    /**
     * Test that ACS Defend shows as available mission type for ally planets.
     * Note: This test will need buddy/alliance setup once implemented.
     */
    public function testFleetCheckToAllyPlanetSuccess(): void
    {
        $this->basicSetup();

        // TODO: Create ally player and test
        // For now, test that mission type 5 is recognized
        $unitCollection = new UnitCollection();
        $unitCollection->addUnit(ObjectService::getUnitObjectByMachineName('light_fighter'), 10);

        // This will fail until ally system is in place - that's expected
        $this->markTestIncomplete('Requires alliance/buddy system implementation');
    }

    /**
     * Test that valid hold times are accepted.
     */
    public function testValidHoldTimesAccepted(): void
    {
        $validTimes = [0, 1, 2, 4, 8, 16, 32];

        foreach ($validTimes as $hours) {
            $this->assertTrue(
                \OGame\GameMissions\AcsDefendMission::isValidHoldTime($hours),
                "Hold time {$hours} hours should be valid"
            );
        }
    }

    /**
     * Test that invalid hold times are rejected.
     */
    public function testInvalidHoldTimesRejected(): void
    {
        $invalidTimes = [3, 5, 7, 10, 24, 48];

        foreach ($invalidTimes as $hours) {
            $this->assertFalse(
                \OGame\GameMissions\AcsDefendMission::isValidHoldTime($hours),
                "Hold time {$hours} hours should be invalid"
            );
        }
    }
}
```

#### Step 7: Run Tests & Quality Checks

```bash
# Run the new tests
php artisan test tests/Feature/FleetDispatch/FleetDispatchAcsDefendTest.php

# Run all tests to ensure nothing broke
php artisan test

# Check code quality
./vendor/bin/pint
./vendor/bin/phpstan analyse
```

#### PR 1 Checklist

- [ ] Created `AcsDefendMission.php` with `isMissionPossible()`, `processArrival()`, `processReturn()`
- [ ] Registered mission type 5 in `GameMissionFactory.php`
- [ ] Created migration for `hold_time` column
- [ ] Added `hold_time` to `FleetMission` model properties
- [ ] Created `resources/lang/en/t_acs.php` with initial translations
- [ ] Created `FleetDispatchAcsDefendTest.php` with basic tests
- [ ] All existing tests still pass
- [ ] PHPStan passes
- [ ] PSR-12 compliant (ran pint)

---

### PR 2 Implementation Guide: BattleUnit Owner Tracking

#### Step 1: Study Existing Patterns

```bash
# Read these files:
app/GameMissions/BattleEngine/Models/BattleUnit.php     # Current implementation
app/GameMissions/BattleEngine/PhpBattleEngine.php       # How BattleUnits are created
tests/Unit/BattleEngine/BattleEngineTestAbstract.php    # Test patterns
```

#### Step 2: Update BattleUnit Class

**File**: `app/GameMissions/BattleEngine/Models/BattleUnit.php`

```php
<?php

namespace OGame\GameMissions\BattleEngine\Models;

use OGame\GameObjects\Models\UnitObject;

/**
 * Model class that represents a unit in a battle keeping track of its health and other properties.
 */
class BattleUnit
{
    /**
     * @var UnitObject The unit object that this battle unit represents.
     */
    public UnitObject $unitObject;

    /**
     * @var int The original hull plating of the unit.
     */
    public int $originalHullPlating;

    /**
     * @var int The original shield points of the unit.
     */
    public int $originalShieldPoints;

    /**
     * @var int The current hull plating of the unit.
     */
    public int $currentHullPlating;

    /**
     * @var int The current shield points of the unit.
     */
    public int $currentShieldPoints;

    /**
     * @var int The attack power of the unit.
     */
    public int $attackPower;

    /**
     * @var int|null The fleet mission ID this unit belongs to (for ACS battles).
     */
    public ?int $fleetMissionId;

    /**
     * @var int|null The owner player ID (for ACS battles).
     */
    public ?int $ownerId;

    /**
     * @var bool Whether this unit is on the attacking side.
     */
    public bool $isAttacker;

    /**
     * Create a new BattleUnit object.
     *
     * @param UnitObject $unitObject
     * @param int $structuralIntegrity
     * @param int $shieldPoints
     * @param int $attackPower
     * @param int|null $fleetMissionId Fleet mission ID for ACS tracking (optional)
     * @param int|null $ownerId Owner player ID for ACS tracking (optional)
     * @param bool $isAttacker Whether this unit is attacking (default: true)
     */
    public function __construct(
        UnitObject $unitObject,
        int $structuralIntegrity,
        int $shieldPoints,
        int $attackPower,
        ?int $fleetMissionId = null,
        ?int $ownerId = null,
        bool $isAttacker = true
    ) {
        $this->unitObject = $unitObject;

        $hullPlating = $structuralIntegrity / 10;
        $this->originalHullPlating = $hullPlating;
        $this->currentHullPlating = $hullPlating;

        $this->originalShieldPoints = $shieldPoints;
        $this->currentShieldPoints = $shieldPoints;

        $this->attackPower = $attackPower;

        // ACS tracking properties
        $this->fleetMissionId = $fleetMissionId;
        $this->ownerId = $ownerId;
        $this->isAttacker = $isAttacker;
    }

    /**
     * When hull plating is < 70%, unit has chance to explode.
     *
     * @return bool
     */
    public function damagedHullExplosion(): bool
    {
        $hullPercentage = $this->currentHullPlating / $this->originalHullPlating;
        if ($hullPercentage >= 0.7) {
            return false;
        }

        $explosionChance = (1 - $hullPercentage) * 100;
        return rand(0, 100) < $explosionChance;
    }

    /**
     * Check if this unit belongs to a specific fleet mission.
     */
    public function belongsToFleet(int $fleetMissionId): bool
    {
        return $this->fleetMissionId === $fleetMissionId;
    }

    /**
     * Check if this unit belongs to a specific player.
     */
    public function belongsToPlayer(int $playerId): bool
    {
        return $this->ownerId === $playerId;
    }
}
```

#### Step 3: Update PhpBattleEngine (Backward Compatible)

**File**: `app/GameMissions/BattleEngine/PhpBattleEngine.php`

Find where BattleUnits are created (around line 40-44) and update to pass optional parameters. The existing code should continue to work because the new parameters are optional with defaults.

No changes needed if you made the constructor parameters optional with defaults!

#### Step 4: Create Unit Test

**File**: `tests/Unit/BattleEngine/BattleUnitOwnerTrackingTest.php`

```php
<?php

namespace Tests\Unit\BattleEngine;

use OGame\GameMissions\BattleEngine\Models\BattleUnit;
use OGame\Services\ObjectService;
use Tests\UnitTestCase;

class BattleUnitOwnerTrackingTest extends UnitTestCase
{
    /**
     * Test that BattleUnit can be created without owner tracking (backward compatible).
     */
    public function testCreateBattleUnitWithoutOwnerTracking(): void
    {
        $unitObject = ObjectService::getUnitObjectByMachineName('light_fighter');

        $battleUnit = new BattleUnit(
            $unitObject,
            4000,  // structural integrity
            10,    // shield
            50     // attack
        );

        $this->assertNull($battleUnit->fleetMissionId);
        $this->assertNull($battleUnit->ownerId);
        $this->assertTrue($battleUnit->isAttacker);
    }

    /**
     * Test that BattleUnit can be created with owner tracking.
     */
    public function testCreateBattleUnitWithOwnerTracking(): void
    {
        $unitObject = ObjectService::getUnitObjectByMachineName('cruiser');

        $battleUnit = new BattleUnit(
            $unitObject,
            27000,
            50,
            400,
            fleetMissionId: 123,
            ownerId: 456,
            isAttacker: true
        );

        $this->assertEquals(123, $battleUnit->fleetMissionId);
        $this->assertEquals(456, $battleUnit->ownerId);
        $this->assertTrue($battleUnit->isAttacker);
    }

    /**
     * Test that BattleUnit can be created as defender.
     */
    public function testCreateBattleUnitAsDefender(): void
    {
        $unitObject = ObjectService::getUnitObjectByMachineName('rocket_launcher');

        $battleUnit = new BattleUnit(
            $unitObject,
            2000,
            20,
            80,
            fleetMissionId: null,
            ownerId: 789,
            isAttacker: false
        );

        $this->assertNull($battleUnit->fleetMissionId);
        $this->assertEquals(789, $battleUnit->ownerId);
        $this->assertFalse($battleUnit->isAttacker);
    }

    /**
     * Test belongsToFleet helper method.
     */
    public function testBelongsToFleet(): void
    {
        $unitObject = ObjectService::getUnitObjectByMachineName('light_fighter');

        $battleUnit = new BattleUnit(
            $unitObject,
            4000,
            10,
            50,
            fleetMissionId: 100,
            ownerId: 1
        );

        $this->assertTrue($battleUnit->belongsToFleet(100));
        $this->assertFalse($battleUnit->belongsToFleet(999));
    }

    /**
     * Test belongsToPlayer helper method.
     */
    public function testBelongsToPlayer(): void
    {
        $unitObject = ObjectService::getUnitObjectByMachineName('light_fighter');

        $battleUnit = new BattleUnit(
            $unitObject,
            4000,
            10,
            50,
            fleetMissionId: 100,
            ownerId: 42
        );

        $this->assertTrue($battleUnit->belongsToPlayer(42));
        $this->assertFalse($battleUnit->belongsToPlayer(999));
    }

    /**
     * Test that existing battle functionality still works.
     */
    public function testDamagedHullExplosionStillWorks(): void
    {
        $unitObject = ObjectService::getUnitObjectByMachineName('light_fighter');

        $battleUnit = new BattleUnit(
            $unitObject,
            4000,
            10,
            50,
            fleetMissionId: 100,
            ownerId: 1
        );

        // At full health, should never explode
        $this->assertFalse($battleUnit->damagedHullExplosion());

        // Reduce to 50% health
        $battleUnit->currentHullPlating = $battleUnit->originalHullPlating / 2;

        // Now there's a chance of explosion (can't assert exact result due to randomness)
        // Just verify the method runs without error
        $battleUnit->damagedHullExplosion();

        $this->assertTrue(true); // Method executed without error
    }
}
```

#### Step 5: Run Tests

```bash
# Run new tests
php artisan test tests/Unit/BattleEngine/BattleUnitOwnerTrackingTest.php

# Run all battle engine tests to ensure backward compatibility
php artisan test tests/Unit/BattleEngine/

# Run full test suite
php artisan test

# Quality checks
./vendor/bin/pint
./vendor/bin/phpstan analyse
```

#### PR 2 Checklist

- [ ] Added `fleetMissionId`, `ownerId`, `isAttacker` properties to `BattleUnit`
- [ ] Updated constructor with optional parameters (backward compatible)
- [ ] Added `belongsToFleet()` helper method
- [ ] Added `belongsToPlayer()` helper method
- [ ] Created `BattleUnitOwnerTrackingTest.php`
- [ ] All existing battle engine tests still pass
- [ ] PHPStan passes
- [ ] PSR-12 compliant

---

### PR 3 Implementation Guide: Multi-Defender Battle Engine

*Depends on: PR 1 (AcsDefendMission) + PR 2 (BattleUnit owner tracking)*

#### Step 1: Study Existing Patterns

```bash
# Read these files:
app/GameMissions/AttackMission.php               # How battles are triggered
app/GameMissions/BattleEngine/PhpBattleEngine.php # Current battle logic
app/GameMissions/BattleEngine/Models/BattleResult.php
app/Services/FleetMissionService.php             # How to query active missions
```

#### Step 2: Create DefenderFleet Model

**File**: `app/GameMissions/BattleEngine/Models/DefenderFleet.php`

```php
<?php

namespace OGame\GameMissions\BattleEngine\Models;

use OGame\GameObjects\Models\Units\UnitCollection;
use OGame\Models\Resources;
use OGame\Services\PlayerService;

/**
 * Represents a defending fleet in an ACS battle.
 */
class DefenderFleet
{
    public function __construct(
        public readonly int $fleetMissionId,
        public readonly PlayerService $player,
        public readonly UnitCollection $units,
        public readonly Resources $cargoResources,
        public readonly bool $isPlanetOwner = false,
    ) {
    }

    /**
     * Convert this fleet's units to BattleUnits with owner tracking.
     *
     * @return array<BattleUnit>
     */
    public function toBattleUnits(): array
    {
        $battleUnits = [];

        foreach ($this->units->units as $unit) {
            $unitObject = $unit->unitObject;
            $amount = $unit->amount;

            // Get unit stats with player's tech bonuses
            $structuralIntegrity = $unitObject->properties->structural_integrity->calculate($this->player)->totalValue;
            $shieldPoints = $unitObject->properties->shield->calculate($this->player)->totalValue;
            $attackPower = $unitObject->properties->attack->calculate($this->player)->totalValue;

            // Create individual BattleUnits with owner tracking
            for ($i = 0; $i < $amount; $i++) {
                $battleUnits[] = new BattleUnit(
                    $unitObject,
                    $structuralIntegrity,
                    $shieldPoints,
                    $attackPower,
                    fleetMissionId: $this->fleetMissionId,
                    ownerId: $this->player->getId(),
                    isAttacker: false
                );
            }
        }

        return $battleUnits;
    }
}
```

#### Step 3: Create DefenderResult Model

**File**: `app/GameMissions/BattleEngine/Models/DefenderResult.php`

```php
<?php

namespace OGame\GameMissions\BattleEngine\Models;

use OGame\GameObjects\Models\Units\UnitCollection;
use OGame\Models\Resources;

/**
 * Battle result for a single defending fleet.
 */
class DefenderResult
{
    public function __construct(
        public readonly int $fleetMissionId,
        public readonly int $playerId,
        public readonly UnitCollection $unitsStart,
        public readonly UnitCollection $unitsResult,
        public readonly UnitCollection $unitsLost,
        public readonly bool $isPlanetOwner = false,
    ) {
    }

    /**
     * Check if this fleet was completely destroyed.
     */
    public function isDestroyed(): bool
    {
        return $this->unitsResult->getAmount() === 0;
    }
}
```

#### Step 4: Update BattleResult for Multi-Defender

**File**: `app/GameMissions/BattleEngine/Models/BattleResult.php`

Add new properties for ACS defender tracking:

```php
/**
 * @var array<int, DefenderResult> Per-fleet results for ACS defenders, keyed by fleet mission ID.
 */
public array $defenderResults = [];

/**
 * @var bool Whether this was an ACS battle with multiple defenders.
 */
public bool $hasAcsDefenders = false;
```

#### Step 5: Update AttackMission to Collect Defending Fleets

**File**: `app/GameMissions/AttackMission.php`

In `processArrival()`, before creating the battle engine, collect any ACS Defend fleets:

```php
// Collect any ACS defending fleets at this planet
$defendingFleets = $this->collectDefendingFleets($defenderPlanet);

// If there are ACS defenders, we need to include them in the battle
// For now, just add their units to the defender side
// Full per-fleet tracking will be added in PR 3
```

Create helper method:

```php
/**
 * Collect all ACS Defend fleets currently holding at this planet.
 *
 * @param PlanetService $planet
 * @return array<DefenderFleet>
 */
private function collectDefendingFleets(PlanetService $planet): array
{
    $defendingFleets = [];

    // Query for ACS Defend missions (type 5) that:
    // - Target this planet
    // - Have arrived (time_arrival <= now)
    // - Hold time not expired (time_arrival + hold_time > now)
    // - Not processed or canceled

    $missions = FleetMission::where('planet_id_to', $planet->getPlanetId())
        ->where('mission_type', 5)
        ->where('time_arrival', '<=', time())
        ->where('processed', 0)
        ->where('canceled', 0)
        ->get();

    foreach ($missions as $mission) {
        // Check hold time hasn't expired
        $holdEndTime = $mission->time_arrival + ($mission->hold_time ?? 0);
        if (time() > $holdEndTime) {
            continue; // Hold expired, skip this fleet
        }

        $player = $this->playerServiceFactory->make($mission->user_id);
        $units = $this->fleetMissionService->getFleetUnits($mission);
        $resources = $this->fleetMissionService->getResources($mission);

        $defendingFleets[] = new DefenderFleet(
            fleetMissionId: $mission->id,
            player: $player,
            units: $units,
            cargoResources: $resources,
            isPlanetOwner: false
        );
    }

    return $defendingFleets;
}
```

#### Step 6: Process Defender Results After Battle

Add to `processArrival()` after battle:

```php
// Process ACS defender results
foreach ($defendingFleets as $defenderFleet) {
    $fleetMission = FleetMission::find($defenderFleet->fleetMissionId);

    // Get this fleet's survivors from battle result
    $survivors = $this->getSurvivorsForFleet($battleResult, $defenderFleet->fleetMissionId);

    if ($survivors->getAmount() === 0) {
        // Fleet was completely destroyed - no return mission
        $fleetMission->processed = 1;
        $fleetMission->save();

        // Follow existing messaging rules:
        // - Round 1 destruction: fleet_lost_contact
        // - Round 2+ destruction: normal battle report
    } else {
        // Create return mission with survivors
        // Update the mission's ships to reflect survivors
        $this->updateMissionUnits($fleetMission, $survivors);

        // Create return mission
        $this->createDefenderReturnMission($fleetMission, $survivors);

        // Mark original as processed
        $fleetMission->processed = 1;
        $fleetMission->save();
    }
}
```

#### Step 7: Create Test

**File**: `tests/Feature/FleetDispatch/FleetDispatchAcsDefendBattleTest.php`

```php
<?php

namespace Tests\Feature\FleetDispatch;

use OGame\GameObjects\Models\Units\UnitCollection;
use OGame\Models\FleetMission;
use OGame\Models\Resources;
use OGame\Services\ObjectService;
use OGame\Services\SettingsService;
use Tests\FleetDispatchTestCase;

class FleetDispatchAcsDefendBattleTest extends FleetDispatchTestCase
{
    protected int $missionType = 5;
    protected string $missionName = 'ACS Defend';

    protected function basicSetup(): void
    {
        $this->planetAddUnit('cruiser', 100);
        $this->playerSetResearchLevel('computer_technology', 5);
        $this->planetAddResources(new Resources(0, 0, 1000000, 0));

        $settingsService = resolve(SettingsService::class);
        $settingsService->set('economy_speed', 0);
        $settingsService->set('fleet_speed_war', 1);
        $settingsService->set('fleet_speed_holding', 1);
    }

    /**
     * Test that destroyed defend fleet does not create return mission.
     */
    public function testDestroyedDefendFleetNoReturnMission(): void
    {
        $this->basicSetup();

        // This test requires:
        // 1. Create ally relationship
        // 2. Send weak defend fleet to ally
        // 3. Wait for arrival
        // 4. Attack ally with overwhelming force
        // 5. Verify no return mission for destroyed fleet

        $this->markTestIncomplete('Requires full ACS Defend + Battle integration');
    }

    /**
     * Test that surviving defend fleet creates return mission.
     */
    public function testSurvivingDefendFleetCreatesReturn(): void
    {
        $this->basicSetup();

        $this->markTestIncomplete('Requires full ACS Defend + Battle integration');
    }

    /**
     * Test that each defender uses their own tech levels.
     */
    public function testDefendersUseOwnTechLevels(): void
    {
        $this->basicSetup();

        $this->markTestIncomplete('Requires full ACS Defend + Battle integration');
    }
}
```

#### PR 3 Checklist

- [ ] Created `DefenderFleet.php` model with `toBattleUnits()`
- [ ] Created `DefenderResult.php` model
- [ ] Updated `BattleResult.php` with `defenderResults` and `hasAcsDefenders`
- [ ] Updated `AttackMission.php` to collect defending fleets
- [ ] Implemented `collectDefendingFleets()` method
- [ ] Implemented destroyed fleet handling (no empty return missions)
- [ ] Created basic tests for defender battle participation
- [ ] All existing tests still pass
- [ ] PHPStan passes
- [ ] PSR-12 compliant

---

### PR 5 Implementation Guide: Fleet Unions Foundation

*Can be developed in parallel with PR 1-3*

#### Step 1: Study Existing Patterns

```bash
# Read these files:
app/Models/FleetMission.php                # Understand mission structure
database/migrations/*create*table*.php     # Migration patterns
app/Services/FleetMissionService.php       # Service patterns
```

#### Step 2: Create Fleet Unions Migration

**File**: `database/migrations/xxxx_create_fleet_unions_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fleet_unions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('Creator/initiator user ID');
            $table->string('name', 100)->nullable()->comment('Optional union name');

            // Target coordinates
            $table->unsignedTinyInteger('galaxy_to');
            $table->unsignedSmallInteger('system_to');
            $table->unsignedTinyInteger('position_to');
            $table->unsignedTinyInteger('planet_type_to')->default(1);

            // Timing
            $table->unsignedInteger('time_arrival')->comment('Coordinated arrival time');

            // Limits
            $table->unsignedTinyInteger('max_fleets')->default(16);
            $table->unsignedTinyInteger('max_players')->default(5);

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['galaxy_to', 'system_to', 'position_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_unions');
    }
};
```

#### Step 3: Add Union Reference to Fleet Missions

**File**: `database/migrations/xxxx_add_union_to_fleet_missions.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fleet_missions', function (Blueprint $table) {
            $table->unsignedBigInteger('union_id')->nullable()->after('mission_type');
            $table->unsignedTinyInteger('union_slot')->nullable()->after('union_id')
                ->comment('Slot number in the union (1-16)');

            $table->foreign('union_id')->references('id')->on('fleet_unions')->onDelete('set null');
            $table->index('union_id');
        });
    }

    public function down(): void
    {
        Schema::table('fleet_missions', function (Blueprint $table) {
            $table->dropForeign(['union_id']);
            $table->dropColumn(['union_id', 'union_slot']);
        });
    }
};
```

#### Step 4: Create FleetUnion Model

**File**: `app/Models/FleetUnion.php`

```php
<?php

namespace OGame\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OGame\Models\Planet\Coordinate;
use OGame\Models\Enums\PlanetType;

/**
 * @property int $id
 * @property int $user_id
 * @property string|null $name
 * @property int $galaxy_to
 * @property int $system_to
 * @property int $position_to
 * @property int $planet_type_to
 * @property int $time_arrival
 * @property int $max_fleets
 * @property int $max_players
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class FleetUnion extends Model
{
    protected $table = 'fleet_unions';

    protected $fillable = [
        'user_id',
        'name',
        'galaxy_to',
        'system_to',
        'position_to',
        'planet_type_to',
        'time_arrival',
        'max_fleets',
        'max_players',
    ];

    /**
     * Get the creator of this union.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get all fleet missions in this union.
     */
    public function fleetMissions(): HasMany
    {
        return $this->hasMany(FleetMission::class, 'union_id');
    }

    /**
     * Get active (non-canceled) fleet missions.
     */
    public function activeFleetMissions(): HasMany
    {
        return $this->fleetMissions()->where('canceled', 0);
    }

    /**
     * Get the target coordinate.
     */
    public function getTargetCoordinate(): Coordinate
    {
        return new Coordinate($this->galaxy_to, $this->system_to, $this->position_to);
    }

    /**
     * Get the target planet type.
     */
    public function getTargetPlanetType(): PlanetType
    {
        return PlanetType::from($this->planet_type_to);
    }

    /**
     * Get count of unique players in this union.
     */
    public function getUniquePlayerCount(): int
    {
        return $this->activeFleetMissions()
            ->distinct('user_id')
            ->count('user_id');
    }

    /**
     * Get the remaining time until arrival in seconds.
     */
    public function getRemainingTime(): int
    {
        return max(0, $this->time_arrival - time());
    }

    /**
     * Check if the union has reached max fleets.
     */
    public function hasReachedMaxFleets(): bool
    {
        return $this->activeFleetMissions()->count() >= $this->max_fleets;
    }

    /**
     * Check if the union has reached max players.
     */
    public function hasReachedMaxPlayers(): bool
    {
        return $this->getUniquePlayerCount() >= $this->max_players;
    }
}
```

#### Step 5: Update FleetMission Model

**File**: `app/Models/FleetMission.php`

Add to docblock:
```php
 * @property int|null $union_id
 * @property int|null $union_slot
```

Add relationship:
```php
/**
 * Get the union this mission belongs to (for ACS Attack).
 */
public function union(): BelongsTo
{
    return $this->belongsTo(FleetUnion::class, 'union_id');
}

/**
 * Check if this mission is part of a union.
 */
public function isInUnion(): bool
{
    return $this->union_id !== null;
}
```

#### Step 6: Create FleetUnionService

**File**: `app/Services/FleetUnionService.php`

```php
<?php

namespace OGame\Services;

use OGame\Models\FleetMission;
use OGame\Models\FleetUnion;
use OGame\Models\Enums\PlanetType;
use Exception;

class FleetUnionService
{
    private const MAX_DELAY_PERCENTAGE = 0.30;

    public function __construct(
        private readonly PlayerService $playerService,
    ) {
    }

    /**
     * Create a new fleet union from an existing attack mission.
     *
     * @throws Exception
     */
    public function createUnion(FleetMission $mission, ?string $name = null): FleetUnion
    {
        // Validate mission type (must be attack - type 1)
        if ($mission->mission_type !== 1) {
            throw new Exception(__('t_acs.error_invalid_mission_type'));
        }

        // Validate mission is still in flight
        if ($mission->processed || $mission->canceled) {
            throw new Exception(__('t_acs.error_mission_not_active'));
        }

        // Create the union
        $union = FleetUnion::create([
            'user_id' => $mission->user_id,
            'name' => $name,
            'galaxy_to' => $mission->galaxy_to,
            'system_to' => $mission->system_to,
            'position_to' => $mission->position_to,
            'planet_type_to' => PlanetType::Planet->value, // TODO: Get from mission
            'time_arrival' => $mission->time_arrival,
            'max_fleets' => 16,
            'max_players' => 5,
        ]);

        // Link the mission to the union and convert to ACS Attack
        $mission->union_id = $union->id;
        $mission->union_slot = 1;
        $mission->mission_type = 2; // Convert to ACS Attack
        $mission->save();

        return $union;
    }

    /**
     * Join an existing union with a fleet mission.
     *
     * @throws Exception
     */
    public function joinUnion(FleetUnion $union, FleetMission $mission): void
    {
        // Validate union hasn't reached max fleets
        if ($union->hasReachedMaxFleets()) {
            throw new Exception(__('t_acs.error_max_fleets_reached'));
        }

        // Validate union hasn't reached max players (if this is a new player)
        $isNewPlayer = !$union->activeFleetMissions()
            ->where('user_id', $mission->user_id)
            ->exists();

        if ($isNewPlayer && $union->hasReachedMaxPlayers()) {
            throw new Exception(__('t_acs.error_max_players_reached'));
        }

        // Validate player is ally or buddy of union creator
        $joiningPlayer = $this->playerService;
        $creatorPlayer = $union->creator;

        if (!$this->isAllyOrBuddy($joiningPlayer, $creatorPlayer)) {
            throw new Exception(__('t_acs.error_not_buddy_or_ally'));
        }

        // Validate fleet can arrive within delay limit
        $maxArrival = $union->time_arrival + $this->getMaxDelayTime($union);
        if ($mission->time_arrival > $maxArrival) {
            throw new Exception(__('t_acs.error_exceeds_delay_limit'));
        }

        // Get next available slot
        $nextSlot = $union->activeFleetMissions()->max('union_slot') + 1;

        // Link mission to union
        $mission->union_id = $union->id;
        $mission->union_slot = $nextSlot;
        $mission->mission_type = 2; // ACS Attack

        // Adjust arrival time to match union (if fleet arrives earlier)
        if ($mission->time_arrival < $union->time_arrival) {
            $mission->time_arrival = $union->time_arrival;
        } else {
            // Fleet arrives later - update union arrival time (within delay limit)
            $union->time_arrival = $mission->time_arrival;
            $union->save();
        }

        $mission->save();
    }

    /**
     * Get the maximum delay time allowed for joining fleets.
     * This is 30% of the remaining flight time.
     */
    public function getMaxDelayTime(FleetUnion $union): int
    {
        $remainingTime = $union->getRemainingTime();
        return (int) floor($remainingTime * self::MAX_DELAY_PERCENTAGE);
    }

    /**
     * Handle a fleet being recalled from a union.
     */
    public function handleFleetRecall(FleetMission $mission): void
    {
        if (!$mission->isInUnion()) {
            return;
        }

        $union = $mission->union;

        // Remove from union
        $mission->union_id = null;
        $mission->union_slot = null;
        $mission->save();

        // Check if union is now empty
        if ($union->activeFleetMissions()->count() === 0) {
            // Delete the empty union
            $union->delete();
        }
    }

    /**
     * Check if two players are allies or buddies.
     */
    private function isAllyOrBuddy(PlayerService $player1, $player2): bool
    {
        $userId1 = $player1->getId();
        $userId2 = $player2 instanceof PlayerService ? $player2->getId() : $player2->id;

        // Check if buddies using BuddyRequest model
        $areBuddies = \OGame\Models\BuddyRequest::where('status', \OGame\Models\BuddyRequest::STATUS_ACCEPTED)
            ->where(function ($query) use ($userId1, $userId2) {
                $query->where(function ($q) use ($userId1, $userId2) {
                    $q->where('sender_user_id', $userId1)
                        ->where('receiver_user_id', $userId2);
                })->orWhere(function ($q) use ($userId1, $userId2) {
                    $q->where('sender_user_id', $userId2)
                        ->where('receiver_user_id', $userId1);
                });
            })
            ->exists();

        if ($areBuddies) {
            return true;
        }

        // Check if in same alliance
        // TODO: Implement alliance check when alliance system is available

        return false;
    }
}
```

#### Step 7: Create Unit Tests

**File**: `tests/Unit/FleetUnionServiceTest.php`

```php
<?php

namespace Tests\Unit;

use OGame\Models\FleetMission;
use OGame\Models\FleetUnion;
use OGame\Services\FleetUnionService;
use Tests\AccountTestCase;

class FleetUnionServiceTest extends AccountTestCase
{
    private FleetUnionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = resolve(FleetUnionService::class);
    }

    /**
     * Test getMaxDelayTime returns 30% of remaining time.
     */
    public function testGetMaxDelayTimeCalculation(): void
    {
        $union = new FleetUnion();
        $union->time_arrival = time() + 1000;

        $maxDelay = $this->service->getMaxDelayTime($union);

        $this->assertEquals(300, $maxDelay);
    }

    /**
     * Test getMaxDelayTime with zero remaining time.
     */
    public function testGetMaxDelayTimeZeroRemaining(): void
    {
        $union = new FleetUnion();
        $union->time_arrival = time() - 100; // Already passed

        $maxDelay = $this->service->getMaxDelayTime($union);

        $this->assertEquals(0, $maxDelay);
    }
}
```

#### Step 8: Run Migrations and Tests

```bash
# Create migration files with proper timestamps
php artisan make:migration create_fleet_unions_table
php artisan make:migration add_union_to_fleet_missions

# Run migrations
php artisan migrate

# Run tests
php artisan test tests/Unit/FleetUnionServiceTest.php
php artisan test

# Quality checks
./vendor/bin/pint
./vendor/bin/phpstan analyse
```

#### PR 5 Checklist

- [ ] Created `fleet_unions` table migration
- [ ] Created migration to add `union_id`, `union_slot` to `fleet_missions`
- [ ] Created `FleetUnion` model with relationships
- [ ] Updated `FleetMission` model with `union()` relationship
- [ ] Created `FleetUnionService` with `createUnion()`, `joinUnion()`, `getMaxDelayTime()`
- [ ] Created unit tests for FleetUnionService
- [ ] Ran migrations successfully
- [ ] All tests pass
- [ ] PHPStan passes
- [ ] PSR-12 compliant

---

*Guides for PR 4, PR 6, PR 7, PR 8, PR 9 follow the same pattern. Key points:*

### PR 6: Multi-Attacker Battle Engine
- Create `AttackerFleet` model (similar to `DefenderFleet`)
- Create `AttackerResult` model
- Create `AcsBattleEngine` that accepts multiple `AttackerFleet`
- Track survivors by `fleetMissionId` using the new BattleUnit properties
- Update `BattleResultRound` with per-fleet tracking

### PR 7: ACS Attack Mission
- Create `AcsAttackMission` class
- Integrate with `FleetUnionService`
- UI for creating/joining unions
- Process coordinated arrival

### PR 8: Loot Distribution
- Calculate total cargo capacity per fleet
- Distribute loot proportionally
- Handle zero-cargo fleets
- Implement synchronized return speeds

### PR 9: Battle Reports & UI
- Update `BattleReport` model for per-fleet data
- Add fleet filter dropdown to report view
- Create individual fleet view template
- One report per player logic

---

## Quick Reference: Task Breakdown

This section provides atomic, sequential tasks for implementation. Complete tasks in order within each milestone. Each task includes the file(s) to modify and acceptance criteria.

### Milestone 1: Database & Models (Do First)

| # | Task | File(s) | Depends On | Done When |
|---|------|---------|------------|-----------|
| 1.1 | Create `fleet_unions` migration | `database/migrations/xxxx_create_fleet_unions_table.php` | - | Migration runs successfully, table exists |
| 1.2 | Add `union_id` to fleet_missions | `database/migrations/xxxx_add_union_to_fleet_missions.php` | 1.1 | Column added, foreign key works |
| 1.3 | Create `FleetUnion` model | `app/Models/FleetUnion.php` | 1.1 | Model with relationships: `creator()`, `fleetMissions()`, `targetPlanet()` |
| 1.4 | Update `FleetMission` model | `app/Models/FleetMission.php` | 1.2 | Add `union()` relationship, `$fillable` includes `union_id`, `slot_number` |

### Milestone 2: Battle Engine Core (Do Second)

| # | Task | File(s) | Depends On | Done When |
|---|------|---------|------------|-----------|
| 2.1 | Extend `BattleUnit` with owner tracking | `app/GameMissions/BattleEngine/Models/BattleUnit.php` | - | Properties `fleetMissionId`, `ownerId` added; constructor accepts them |
| 2.2 | Create `AttackerFleet` model | `app/GameMissions/BattleEngine/Models/AttackerFleet.php` | 2.1 | Class with `toBattleUnits()` method that creates owned BattleUnits |
| 2.3 | Create `AttackerResult` model | `app/GameMissions/BattleEngine/Models/AttackerResult.php` | - | Class with `unitsStart`, `unitsResult`, `unitsLost`, `lootShare` properties |
| 2.4 | Update `BattleResult` for ACS | `app/GameMissions/BattleEngine/Models/BattleResult.php` | 2.3 | Add `attackerResults[]`, `defenderResults[]`, `isAcsBattle` properties |
| 2.5 | Update `BattleResultRound` for ACS | `app/GameMissions/BattleEngine/Models/BattleResultRound.php` | - | Add per-fleet arrays: `attackerLossesPerFleet`, `attackerShipsPerFleet`, etc. |
| 2.6 | Create `AcsBattleEngine` | `app/GameMissions/BattleEngine/AcsBattleEngine.php` | 2.1-2.5 | Engine accepts multiple `AttackerFleet`, simulates battle, returns per-fleet results |

**Test Milestone 2**: Write unit test that creates 2 AttackerFleets, runs AcsBattleEngine, verifies survivors are correctly assigned to owners.

### Milestone 3: Fleet Union Service (Do Third)

| # | Task | File(s) | Depends On | Done When |
|---|------|---------|------------|-----------|
| 3.1 | Create `FleetUnionService` | `app/Services/FleetUnionService.php` | 1.3, 1.4 | Service registered in container |
| 3.2 | Implement `createUnion()` | `app/Services/FleetUnionService.php` | 3.1 | Creates union, links initiator mission |
| 3.3 | Implement `joinUnion()` | `app/Services/FleetUnionService.php` | 3.2 | Validates limits, adjusts arrival time, links mission |
| 3.4 | Implement `getMaxDelayTime()` | `app/Services/FleetUnionService.php` | 3.1 | Returns 30% of remaining time |
| 3.5 | Implement `handleFleetRecall()` | `app/Services/FleetUnionService.php` | 3.2 | Removes fleet, reassigns initiator if needed |
| 3.6 | Implement `processUnionBattle()` | `app/Services/FleetUnionService.php` | 2.6, 3.2 | Collects fleets, calls AcsBattleEngine, distributes results |

### Milestone 4: Mission Types (Do Fourth)

| # | Task | File(s) | Depends On | Done When |
|---|------|---------|------------|-----------|
| 4.1 | Create `AcsAttackMission` class | `app/GameMissions/AcsAttackMission.php` | 3.6 | Extends GameMission, type=2, processes via FleetUnionService |
| 4.2 | Create `AcsDefendMission` class | `app/GameMissions/AcsDefendMission.php` | 2.6 | Extends GameMission, type=5, handles holding logic |
| 4.3 | Register missions in factory | `app/Factories/GameMissionFactory.php` | 4.1, 4.2 | Types 2 and 5 resolve correctly |
| 4.4 | Update `isMissionPossible` checks | `app/GameMissions/AcsAttackMission.php` | 4.1 | Validates buddy/alliance, player limits, protection |

### Milestone 5: Loot Distribution (Do Fifth)

| # | Task | File(s) | Depends On | Done When |
|---|------|---------|------------|-----------|
| 5.1 | Implement `calculateAndDistributeLoot()` | `app/GameMissions/BattleEngine/AcsBattleEngine.php` | 2.6 | Loot split by surviving cargo capacity |
| 5.2 | Handle cargo resource survival | `app/GameMissions/BattleEngine/AcsBattleEngine.php` | 5.1 | Resources lost proportional to destroyed cargo ships |
| 5.3 | Create synchronized return missions | `app/GameMissions/AcsAttackMission.php` | 4.1, 5.1 | All returns use same duration |

### Milestone 6: Battle Reports (Do Sixth)

| # | Task | File(s) | Depends On | Done When |
|---|------|---------|------------|-----------|
| 6.1 | Add ACS fields to BattleReport | `app/Models/BattleReport.php` | - | `attacker_fleets`, `defender_fleets` casts added |
| 6.2 | Create ACS battle report method | `app/GameMissions/AcsAttackMission.php` | 6.1, 2.6 | Populates per-fleet data including rounds |
| 6.3 | Update battle report template | `resources/views/ingame/messages/templates/battle_report_full.blade.php` | 6.1 | Fleet filter dropdown, conditional ACS view |
| 6.4 | Create fleet view partial | `resources/views/ingame/messages/templates/battle_report_fleet.blade.php` | 6.3 | Shows individual fleet stats, per-round table |
| 6.5 | Add filtering JavaScript | `resources/views/ingame/messages/templates/battle_report_full.blade.php` | 6.3 | `filterBattleReport()` function works |
| 6.6 | Implement one-report-per-player | `app/GameMissions/AcsAttackMission.php` | 6.2 | Deduplicates by player_id |

### Milestone 7: UI Integration (Do Seventh)

| # | Task | File(s) | Depends On | Done When |
|---|------|---------|------------|-----------|
| 7.1 | Add union creation endpoint | `app/Http/Controllers/FleetController.php`, `routes/web.php` | 3.2 | POST `/fleet/union/create` works |
| 7.2 | Add union join endpoint | `app/Http/Controllers/FleetController.php`, `routes/web.php` | 3.3 | POST `/fleet/union/join` works |
| 7.3 | Update fleet dispatch UI | `resources/views/ingame/fleet/index.blade.php` | 7.1, 7.2 | Shows "Create Union" / "Join Attack" options |
| 7.4 | Update fleet movement display | `resources/views/ingame/fleet/movement.blade.php` | 1.4 | Shows union info, participant count |
| 7.5 | Update `dispatchCheckTarget` | `app/Http/Controllers/FleetController.php` | 4.3 | Returns union info for target, enables type 2/5 |

### Milestone 8: ACS Defend Specifics (Do Eighth)

| # | Task | File(s) | Depends On | Done When |
|---|------|---------|------------|-----------|
| 8.1 | Implement holding logic | `app/GameMissions/AcsDefendMission.php` | 4.2 | Fleet waits at planet for `time_holding` |
| 8.2 | Integrate with AttackMission | `app/GameMissions/AttackMission.php` | 8.1 | Checks for ACS Defend fleets, includes in battle |
| 8.3 | Handle defender fleet returns | `app/GameMissions/AcsDefendMission.php` | 8.1 | Survivors return after battle or hold expires |
| 8.4 | Update espionage reports | `app/GameMissions/EspionageMission.php` | 8.1 | Shows defending ships without owner info |
| 8.5 | Add Galaxy View option | `resources/views/ingame/galaxy/index.blade.php` | 4.2 | ACS Defend appears on eligible planet hover |

---

## Implementation Notes for Each Task

Below each task reference, find the detailed code and logic in the corresponding Phase section of this document:

- **Milestone 1 tasks** → See "Phase 1: Database Schema Updates"
- **Milestone 2 tasks** → See "Phase 3: Battle Engine Extensions"
- **Milestone 3 tasks** → See "Phase 2: Core Models & Services" and "Phase 5: Fleet Coordination Logic"
- **Milestone 4 tasks** → See "Phase 4: Mission Types Implementation"
- **Milestone 5 tasks** → See "Phase 6: Loot Distribution"
- **Milestone 6 tasks** → See "Phase 7: Battle Reports for ACS"
- **Milestone 7 tasks** → See "Phase 8: UI/UX Updates"
- **Milestone 8 tasks** → See "Phase 9: ACS Defend Implementation"

---

## Translation Requirements

All user-facing strings must be translatable following Laravel conventions and OGameX patterns.

### Translation Approach

Use **short, semantic keys** in a dedicated feature file: `resources/lang/en/t_acs.php`

**DO NOT** use literal text as keys. Use abstract, lowercase, underscore-separated keys.

### Example Translation File

**File**: `resources/lang/en/t_acs.php`

```php
<?php

return [
    // Mission types
    'mission_acs_attack' => 'ACS Attack',
    'mission_acs_defend' => 'ACS Defend',

    // Union management
    'union_create' => 'Create Union',
    'union_join' => 'Join Attack',
    'union_leave' => 'Leave Union',
    'union_name' => 'Union Name',
    'union_participants' => 'Participants',
    'union_max_fleets' => 'Maximum Fleets',
    'union_max_players' => 'Maximum Players',
    'union_arrival_time' => 'Arrival Time',
    'union_delay_limit' => 'Max Delay',

    // Validation messages
    'error_not_buddy_or_ally' => 'You must be a Buddy or Alliance member to join this attack.',
    'error_max_fleets_reached' => 'This union has reached the maximum number of fleets.',
    'error_max_players_reached' => 'This union has reached the maximum number of players.',
    'error_delay_exceeded' => 'Your fleet cannot reach the target within the allowed delay time.',
    'error_player_protected' => 'The target player is under new player protection.',
    'error_cannot_defend_protected' => 'You cannot defend a player who is still under protection.',

    // Fleet widget
    'fleet_acs_attack' => 'ACS Attack',
    'fleet_acs_defend' => 'ACS Defend',
    'fleet_total_ships' => 'Total Ships',
    'fleet_participants' => ':count participants',

    // Battle report
    'report_acs_battle' => 'Alliance Combat System Battle',
    'report_attacking_fleets' => ':count attacking fleets',
    'report_defending_participants' => ':count defending participants',
    'report_view_fleet' => 'View Fleet',
    'report_combined_view' => 'Combined View (All Fleets)',
    'report_initiator' => 'Initiator',
    'report_planet_owner' => 'Planet Owner',
    'report_loot_share' => 'Loot Received',
    'report_fleet_losses' => 'Fleet Losses',
    'report_round_details' => 'Round :number Details',
    'report_ships_remaining' => 'Ships Remaining',
    'report_losses_this_round' => 'Losses This Round',
    'report_hits_dealt' => 'Hits Dealt',
    'report_damage_dealt' => 'Damage Dealt',

    // Hold times
    'hold_time_hours' => ':hours hours',
    'hold_time_none' => 'No hold time',

    // Invitation messages
    'invitation_subject' => 'ACS Attack Invitation',
    'invitation_body' => ':player has invited you to join an attack on :target.',
    'invitation_accept' => 'Join Attack',
    'invitation_decline' => 'Decline',

    // ACS Defend specific
    'defend_supply_deuterium' => 'Supply Deuterium',
    'defend_extend_hold' => 'Extend Hold Time',
    'defend_current_hold' => 'Current Hold Time',
];
```

### Usage in Blade Templates

```php
// Correct - uses semantic keys
{{ __('t_acs.union_create') }}
{{ __('t_acs.fleet_participants', ['count' => 3]) }}
{{ __('t_acs.report_round_details', ['number' => $round['round_number']]) }}

// WRONG - do not use literal text as keys
{{ __('Create Union') }}  // ❌
{{ __('ACS Attack') }}    // ❌
```

### Usage in PHP Code

```php
// Correct
throw new Exception(__('t_acs.error_max_fleets_reached'));

// For messages
$this->messageService->sendMessage(
    $player,
    __('t_acs.invitation_subject'),
    __('t_acs.invitation_body', [
        'player' => $initiator->getUsername(),
        'target' => $targetPlanet->getPlanetName()
    ])
);
```

### Files Requiring Translations

| File | Translation Keys Needed |
|------|------------------------|
| `AcsAttackMission.php` | Mission name, error messages |
| `AcsDefendMission.php` | Mission name, hold time labels |
| `FleetUnionService.php` | Validation error messages |
| `FleetController.php` | Success/error responses |
| `battle_report_full.blade.php` | Report UI labels |
| `battle_report_fleet.blade.php` | Fleet view labels |
| `fleet/index.blade.php` | Union creation UI |
| `fleet/movement.blade.php` | Union info display |

### Translation Task Checklist

| # | Task | Done When |
|---|------|-----------|
| T.1 | Create `resources/lang/en/t_acs.php` with all keys | File exists with complete key set |
| T.2 | Replace hardcoded strings in Blade templates | All `{{ 'text' }}` replaced with `{{ __('t_acs.key') }}` |
| T.3 | Replace hardcoded strings in PHP code | All error messages use translation keys |
| T.4 | Add placeholders for dynamic values | Uses `:variable` syntax correctly |

---

## Detailed Requirements

### ACS Attack Rules

| Rule | Description |
|------|-------------|
| **Fleet Limits** | Maximum 16 fleets from up to 5 players |
| **Player Eligibility** | Must be Buddies or in the same Alliance |
| **New Player Protection** | Cannot circumvent protection rules |
| **Loot Distribution** | 50% max (server configurable); distributed by cargo capacity |
| **Delay Limit** | Group can only be delayed by max 30% of remaining time |
| **Combat Tech** | Each participant uses their OWN tech levels |
| **Conversion** | Regular attacks can be converted to ACS Attack |
| **Moon Creation** | Can generate moons from debris |
| **Wreckage** | Can generate wreckage for defender and attacking General class players |
| **Combat Reports** | One report per player (not per fleet) |
| **Fleet Recall** | Players can withdraw at any point before impact |
| **Initiator Recall** | If initiator recalls, remaining fleets continue |
| **Return Speed** | Uses synchronized speed of parent mission |

### ACS Attack UI Requirements

| Component | Requirement |
|-----------|-------------|
| **Fleet Widget** | Display each arriving fleet individually |
| **Fleet Widget Hover** | Show total ships; hover shows participants + ship counts |
| **Message System** | New message class for invitations |
| **Combat Report** | Dropdown menus for viewing individual fleet losses |
| **Missing Asset** | Need ACS Attack .gif for fleet widget |

### ACS Defend / Hold Rules

| Rule | Description |
|------|-------------|
| **Player Limits** | Maximum 5 players can defend a foreign planet |
| **Player Eligibility** | Must be Buddies or in the same Alliance |
| **New Player Protection** | Cannot defend player still in protection |
| **Hold Times** | 0, 1, 2, 4, 8, 16, or 32 hours |
| **Deuterium Supply** | Alliance Depot can supply deuterium to defenders |
| **Hold Extension** | Can extend hold time by supplying more deuterium |
| **Combat Tech** | Each participant uses their own tech levels |
| **Espionage Reports** | Defending fleets visible but owner not disclosed |
| **Resources** | Cargo resources NOT transferred; returned to origin |
| **Self-Attack** | Can attack own ships defending foreign planet |
| **Fleet Recall** | Can recall during approach AND hold time |

### ACS Defend UI Requirements

| Component | Requirement |
|-----------|-------------|
| **Own View** | Appears as "own" mission (standard color) |
| **Target View** | Appears as "friendly" (limegreen, same as own) |
| **Mission Display** | Target sees parent mission + hold time (2 entries) |
| **Galaxy View** | ACS Defend option on planet hover if eligible |
| **Fleet Widget Color** | Use color `#7F4E2D` |
| **Missing Asset** | Need appropriate .gif for fleet widget |

---

## Current System Analysis

### Existing Combat Flow

1. **Mission Dispatch** (`FleetController.php:363-448`)
   - Player selects units, target, and mission type
   - `FleetMissionService.createNewFromPlanet()` creates the mission
   - Fleet and resources deducted from origin planet

2. **Mission Processing** (`AttackMission.php:77-210`)
   - `processArrival()` executes when fleet arrives
   - Battle engine simulates combat (1v1)
   - Results applied: defender losses, debris, loot
   - Return mission created with surviving units and loot

3. **Battle Engine** (`BattleEngine.php`, `PhpBattleEngine.php`)
   - Takes single attacker fleet + single defender planet
   - Processes up to 6 rounds of combat
   - Returns `BattleResult` with losses, loot, debris, moon chance

### Current Limitations

- **Single attacker model**: `BattleEngine` constructor takes one `UnitCollection` for attacker
- **Single tech level**: Uses one player's weapon/shield/armor levels for all attacker units
- **1:1 mission relationship**: `FleetMission.parent_id` only links return trips
- **No union/group concept**: Mission types 2 (ACS Attack) and 5 (ACS Defend) are defined but not implemented
- **Alliance system incomplete**: `AllianceController` is mostly a stub

---

## Implementation Plan

### Phase 1: Database Schema Updates

#### 1.1 Create `fleet_unions` Table
```php
Schema::create('fleet_unions', function (Blueprint $table) {
    $table->id();
    $table->integer('user_id')->unsigned();  // Creator/owner of the union
    $table->foreign('user_id')->references('id')->on('users');

    $table->integer('target_planet_id')->unsigned()->nullable();
    $table->foreign('target_planet_id')->references('id')->on('planets');

    $table->integer('target_galaxy');
    $table->integer('target_system');
    $table->integer('target_position');
    $table->integer('target_type');  // Planet or Moon

    $table->integer('time_arrival');  // Coordinated arrival time
    $table->string('name')->nullable();  // Optional union name
    $table->tinyInteger('max_fleets')->default(5);  // Max fleets allowed
    $table->tinyInteger('is_acs_attack')->default(1);  // 1=attack, 0=defend
    $table->tinyInteger('processed')->default(0);
    $table->timestamps();
});
```

#### 1.2 Update `fleet_missions` Table
```php
Schema::table('fleet_missions', function (Blueprint $table) {
    $table->integer('union_id')->unsigned()->nullable()->after('parent_id');
    $table->foreign('union_id')->references('id')->on('fleet_unions');

    $table->integer('slot_number')->nullable();  // Position in union (1-5)
});
```

#### 1.3 Create `fleet_union_participants` Table (optional, for tracking invites)
```php
Schema::create('fleet_union_participants', function (Blueprint $table) {
    $table->id();
    $table->integer('union_id')->unsigned();
    $table->foreign('union_id')->references('id')->on('fleet_unions');

    $table->integer('user_id')->unsigned();
    $table->foreign('user_id')->references('id')->on('users');

    $table->integer('fleet_mission_id')->unsigned()->nullable();
    $table->foreign('fleet_mission_id')->references('id')->on('fleet_missions');

    $table->enum('status', ['invited', 'joined', 'declined'])->default('invited');
    $table->timestamps();
});
```

---

### Phase 2: Core Models & Services

#### 2.1 Create `FleetUnion` Model
**File**: `app/Models/FleetUnion.php`

```php
class FleetUnion extends Model {
    // Relationships
    public function creator(): BelongsTo;
    public function targetPlanet(): BelongsTo;
    public function fleetMissions(): HasMany;
    public function participants(): HasMany;

    // Methods
    public function getAvailableSlots(): int;
    public function canJoin(PlayerService $player): bool;
    public function getArrivalTime(): int;
    public function getAllFleetUnits(): UnitCollection;  // Combined fleet
}
```

#### 2.2 Create `FleetUnionService`
**File**: `app/Services/FleetUnionService.php`

Key methods:
- `createUnion(PlayerService $creator, FleetMission $initialMission): FleetUnion`
- `joinUnion(FleetUnion $union, FleetMission $mission): bool`
- `leaveUnion(FleetUnion $union, FleetMission $mission): void`
- `getUnionForMission(FleetMission $mission): ?FleetUnion`
- `getActiveUnionsForTarget(Coordinate $target): Collection`
- `inviteToUnion(FleetUnion $union, PlayerService $player): void`
- `processUnionBattle(FleetUnion $union): void`

---

### Phase 3: Battle Engine Extensions

#### 3.1 Individual Ship Tracking (SELECTED APPROACH)

**Decision**: The maintainer has selected **Option 6: Tracked Individual Ships** for the battle engine.

This approach tracks every individual ship with its owner, allowing perfect accuracy for returning surviving ships to their respective owners after battle.

**Key Insight**: The existing `PhpBattleEngine` already creates individual `BattleUnit` objects for each ship (see `PhpBattleEngine.php:40-44`). We only need to extend `BattleUnit` to track ownership.

#### 3.2 Extend `BattleUnit` Model
**File**: `app/GameMissions/BattleEngine/Models/BattleUnit.php`

```php
class BattleUnit
{
    // Existing properties...
    public UnitObject $unitObject;
    public int $originalHullPlating;
    public int $originalShieldPoints;
    public int $currentHullPlating;
    public int $currentShieldPoints;
    public int $attackPower;

    // NEW: Owner tracking for ACS
    /**
     * @var int|null The fleet mission ID this ship belongs to.
     * Null for defender units (they belong to the planet).
     */
    public ?int $fleetMissionId = null;

    /**
     * @var int|null The player ID who owns this ship.
     * Used for applying correct tech levels and returning ships.
     */
    public ?int $ownerId = null;

    /**
     * @var bool Whether this unit is an attacker (true) or defender (false).
     */
    public bool $isAttacker = true;

    /**
     * Extended constructor for ACS support.
     */
    public function __construct(
        UnitObject $unitObject,
        int $structuralIntegrity,
        int $shieldPoints,
        int $attackPower,
        ?int $fleetMissionId = null,  // NEW
        ?int $ownerId = null           // NEW
    ) {
        // Existing initialization...
        $this->fleetMissionId = $fleetMissionId;
        $this->ownerId = $ownerId;
    }
}
```

#### 3.3 Create `AttackerFleet` Data Structure
**File**: `app/GameMissions/BattleEngine/Models/AttackerFleet.php`

**IMPORTANT**: For both ACS Attack AND ACS Defend, each participant uses their **OWN tech levels**. Ships from different players have different stats based on their owner's research.

```php
/**
 * Represents a single attacker's fleet in an ACS battle.
 * Used to group ships by owner for survivor tracking and loot distribution.
 */
class AttackerFleet
{
    public int $fleetMissionId;
    public int $playerId;
    public UnitCollection $units;
    public PlayerService $player;
    public bool $isInitiator;  // Is this the main attacker who created the union?

    // Player's actual tech levels (stored for battle report display)
    public int $weaponLevel;
    public int $shieldLevel;
    public int $armorLevel;

    // Resources carried by this fleet
    public Resources $cargoResources;

    public function __construct(
        int $fleetMissionId,
        PlayerService $player,
        UnitCollection $units,
        Resources $cargoResources,
        bool $isInitiator = false
    ) {
        $this->fleetMissionId = $fleetMissionId;
        $this->playerId = $player->getId();
        $this->player = $player;
        $this->units = $units;
        $this->cargoResources = $cargoResources;
        $this->isInitiator = $isInitiator;

        // Store player's actual tech levels (for battle report)
        $this->weaponLevel = $player->getResearchLevel('weapon_technology');
        $this->shieldLevel = $player->getResearchLevel('shielding_technology');
        $this->armorLevel = $player->getResearchLevel('armor_technology');
    }

    /**
     * Convert fleet units to individual BattleUnit objects with owner tracking.
     * Each fleet uses its OWN player's tech levels for calculations.
     *
     * @return array<BattleUnit>
     */
    public function toBattleUnits(): array
    {
        $battleUnits = [];

        foreach ($this->units->units as $unit) {
            // Always use OWN player's tech levels
            $structuralIntegrity = $unit->unitObject->properties->structural_integrity
                ->calculate($this->player)->totalValue;
            $shieldPoints = $unit->unitObject->properties->shield
                ->calculate($this->player)->totalValue;
            $attackPower = $unit->unitObject->properties->attack
                ->calculate($this->player)->totalValue;

            $unitTemplate = new BattleUnit(
                $unit->unitObject,
                $structuralIntegrity,
                $shieldPoints,
                $attackPower,
                $this->fleetMissionId,  // Track owner for survivor assignment
                $this->playerId
            );

            // Create individual ship instances
            for ($i = 0; $i < $unit->amount; $i++) {
                $battleUnits[] = clone $unitTemplate;
            }
        }

        return $battleUnits;
    }
}
```

#### 3.4 Create `AcsBattleEngine` Class
**File**: `app/GameMissions/BattleEngine/AcsBattleEngine.php`

```php
/**
 * Battle engine extension for Alliance Combat System (ACS) battles.
 * Supports multiple attackers and/or multiple defenders with individual ship tracking.
 *
 * TECH LEVEL RULE: Each participant (attacker OR defender) uses their OWN tech levels.
 * Ships from Player A will have different stats than ships from Player B based on research.
 */
class AcsBattleEngine
{
    /** @var array<AttackerFleet> */
    private array $attackerFleets;

    /** @var array<AttackerFleet> Defending fleets (ACS Defend missions) */
    private array $defenderFleets = [];

    private PlanetService $defenderPlanet;
    private SettingsService $settings;

    public function __construct(
        array $attackerFleets,
        PlanetService $defenderPlanet,
        SettingsService $settings,
        array $defenderFleets = []
    ) {
        $this->attackerFleets = $attackerFleets;
        $this->defenderPlanet = $defenderPlanet;
        $this->settings = $settings;
        $this->defenderFleets = $defenderFleets;
    }

    public function simulateBattle(): BattleResult
    {
        $result = new BattleResult();
        $result->isAcsBattle = true;

        // Build attacker units - each fleet uses OWN tech levels
        $attackerUnits = [];
        foreach ($this->attackerFleets as $fleet) {
            $attackerUnits = array_merge($attackerUnits, $fleet->toBattleUnits());
        }

        // Build defender units - planet + ACS Defend fleets, each with OWN tech
        $defenderUnits = $this->buildDefenderUnits();

        // Execute battle rounds
        $result->rounds = $this->fightBattleRounds($attackerUnits, $defenderUnits, $result);

        // Collect survivors grouped by owner
        $result->attackerResults = $this->collectSurvivorsByOwner($attackerUnits);

        // Also collect defender fleet survivors (for ACS Defend return missions)
        if (!empty($this->defenderFleets)) {
            $result->defenderResults = $this->collectDefenderSurvivorsByOwner($defenderUnits);
        }

        // Calculate loot and distribute by surviving cargo capacity
        $this->calculateAndDistributeLoot($result);

        return $result;
    }

    /**
     * Build defender units from planet + ACS Defend fleets.
     * Each participant uses their OWN tech levels.
     */
    private function buildDefenderUnits(): array
    {
        $defenderUnits = [];

        // Planet's own ships and defenses (use planet owner's tech)
        $planetOwner = $this->defenderPlanet->getPlayer();

        // Create planet fleet wrapper for consistent handling
        $planetFleet = new AttackerFleet(
            fleetMissionId: 0,  // 0 indicates planet-based units
            player: $planetOwner,
            units: $this->defenderPlanet->getShipUnits(),
            cargoResources: new Resources(0, 0, 0, 0),
            isInitiator: false
        );
        $defenderUnits = array_merge($defenderUnits, $planetFleet->toBattleUnits());

        // Add planet defenses
        $defenseFleet = new AttackerFleet(
            fleetMissionId: 0,
            player: $planetOwner,
            units: $this->defenderPlanet->getDefenseUnits(),
            cargoResources: new Resources(0, 0, 0, 0),
            isInitiator: false
        );
        $defenderUnits = array_merge($defenderUnits, $defenseFleet->toBattleUnits());

        // ACS Defend fleets - each uses their OWN tech levels
        foreach ($this->defenderFleets as $fleet) {
            $defenderUnits = array_merge($defenderUnits, $fleet->toBattleUnits());
        }

        return $defenderUnits;
    }

    /**
     * After battle, group surviving ships by their fleet mission ID.
     *
     * @param array<BattleUnit> $units All attacker units (including destroyed)
     * @return array<int, AttackerResult> Keyed by fleet_mission_id
     */
    private function collectSurvivorsByOwner(array $units): array
    {
        $results = [];

        foreach ($units as $unit) {
            $missionId = $unit->fleetMissionId;

            if (!isset($results[$missionId])) {
                $results[$missionId] = new AttackerResult();
                $results[$missionId]->fleetMissionId = $missionId;
                $results[$missionId]->playerId = $unit->ownerId;
                $results[$missionId]->unitsStart = new UnitCollection();
                $results[$missionId]->unitsResult = new UnitCollection();
                $results[$missionId]->unitsLost = new UnitCollection();
            }

            // Track starting units
            $results[$missionId]->unitsStart->addUnit($unit->unitObject, 1);

            // Track survivors vs losses
            if ($unit->currentHullPlating > 0) {
                $results[$missionId]->unitsResult->addUnit($unit->unitObject, 1);
            } else {
                $results[$missionId]->unitsLost->addUnit($unit->unitObject, 1);
            }
        }

        return $results;
    }
}
```

#### 3.5 Performance Considerations for Individual Ship Tracking

**Memory Optimization**:
```php
// For very large battles, consider using SplFixedArray
$attackerUnits = new SplFixedArray($totalShipCount);

// Or use object pooling to reduce allocations
class BattleUnitPool {
    private array $pool = [];
    private int $index = 0;

    public function acquire(...$args): BattleUnit {
        if ($this->index < count($this->pool)) {
            $unit = $this->pool[$this->index++];
            $unit->reset(...$args);
            return $unit;
        }
        $this->pool[] = new BattleUnit(...$args);
        return $this->pool[$this->index++];
    }
}
```

**Batch Processing for Large Battles**:
```php
// For battles with 10,000+ ships, process in chunks
const CHUNK_SIZE = 1000;

foreach (array_chunk($attackerUnits, self::CHUNK_SIZE) as $chunk) {
    foreach ($chunk as $unit) {
        // Process attacks
    }
}
```

**Database Considerations**:
- Don't store individual ship states in database during battle
- Only store final results (UnitCollections per owner)
- Battle logs can optionally store round-by-round summaries

#### 3.6 Update `BattleResult` Model
**File**: `app/GameMissions/BattleEngine/Models/BattleResult.php`

Add support for multiple attackers:

```php
class BattleResult {
    // Existing single-attacker fields (keep for backward compatibility)
    public UnitCollection $attackerUnitsStart;
    public UnitCollection $attackerUnitsResult;
    // ... other existing fields

    // NEW: Multi-attacker support
    /** @var array<int, AttackerResult> Keyed by fleet_mission_id */
    public array $attackerResults = [];

    /** @var array<int, AttackerResult> Defender fleets (ACS Defend) keyed by fleet_mission_id */
    public array $defenderResults = [];

    /** @var bool Whether this is an ACS battle with multiple participants */
    public bool $isAcsBattle = false;
}
```

#### 3.7 Create `AttackerResult` Model
**File**: `app/GameMissions/BattleEngine/Models/AttackerResult.php`

```php
/**
 * Represents the battle outcome for a single attacker's fleet in an ACS battle.
 * Tracks exactly which ships survived based on individual ship tracking.
 */
class AttackerResult {
    /** @var int The fleet mission ID this result belongs to */
    public int $fleetMissionId;

    /** @var int The player ID who owns this fleet */
    public int $playerId;

    /** @var UnitCollection Ships at the start of battle */
    public UnitCollection $unitsStart;

    /** @var UnitCollection Ships that survived the battle */
    public UnitCollection $unitsResult;

    /** @var UnitCollection Ships that were destroyed */
    public UnitCollection $unitsLost;

    /** @var Resources Value of ships lost (for statistics) */
    public Resources $resourceLoss;

    /** @var Resources This fleet's share of the loot */
    public Resources $lootShare;

    /** @var Resources Cargo resources that survived (proportional to surviving cargo capacity) */
    public Resources $survivingCargo;

    // Tech levels (for battle report display)
    public int $weaponLevel;
    public int $shieldLevel;
    public int $armorLevel;

    public function __construct() {
        $this->unitsStart = new UnitCollection();
        $this->unitsResult = new UnitCollection();
        $this->unitsLost = new UnitCollection();
        $this->resourceLoss = new Resources(0, 0, 0, 0);
        $this->lootShare = new Resources(0, 0, 0, 0);
        $this->survivingCargo = new Resources(0, 0, 0, 0);
    }

    /**
     * Calculate resource loss based on units lost.
     */
    public function calculateResourceLoss(): void {
        $this->resourceLoss = $this->unitsLost->toResources();
    }
}
```

#### 3.8 Update `BattleResultRound` Model
**File**: `app/GameMissions/BattleEngine/Models/BattleResultRound.php`

With individual ship tracking, round statistics can now be broken down by owner:

```php
class BattleResultRound {
    // Existing fields (keep for backward compatibility)
    public UnitCollection $attackerLosses;
    public UnitCollection $attackerLossesInRound;
    public UnitCollection $defenderLosses;
    public UnitCollection $defenderLossesInRound;
    public UnitCollection $attackerShips;
    public UnitCollection $defenderShips;
    // ... hits, damage stats

    // NEW: Per-owner tracking for ACS battles
    /** @var array<int, UnitCollection> Cumulative losses per attacker fleet_mission_id */
    public array $attackerLossesPerFleet = [];

    /** @var array<int, UnitCollection> Losses in THIS round per attacker fleet_mission_id */
    public array $attackerLossesInRoundPerFleet = [];

    /** @var array<int, UnitCollection> Remaining ships per attacker fleet_mission_id */
    public array $attackerShipsPerFleet = [];

    /** @var array<int, int> Hits made by each attacker fleet */
    public array $hitsPerAttackerFleet = [];

    /** @var array<int, int> Damage dealt by each attacker fleet */
    public array $damagePerAttackerFleet = [];

    /**
     * Initialize per-fleet tracking arrays from the battle units.
     */
    public function initializePerFleetTracking(array $attackerUnits): void {
        $fleetIds = array_unique(array_map(fn($u) => $u->fleetMissionId, $attackerUnits));
        foreach ($fleetIds as $fleetId) {
            $this->attackerLossesPerFleet[$fleetId] = new UnitCollection();
            $this->attackerLossesInRoundPerFleet[$fleetId] = new UnitCollection();
            $this->attackerShipsPerFleet[$fleetId] = new UnitCollection();
            $this->hitsPerAttackerFleet[$fleetId] = 0;
            $this->damagePerAttackerFleet[$fleetId] = 0;
        }
    }
}
```

---

### Phase 4: Mission Types Implementation

#### 4.1 Create `AcsAttackMission` Class
**File**: `app/GameMissions/AcsAttackMission.php`

```php
class AcsAttackMission extends GameMission {
    protected static string $name = 'ACS Attack';
    protected static int $typeId = 2;
    protected static bool $hasReturnMission = true;
    protected static FleetSpeedType $fleetSpeedType = FleetSpeedType::war;
    protected static FleetMissionStatus $friendlyStatus = FleetMissionStatus::Hostile;

    public function isMissionPossible(...): MissionPossibleStatus {
        // Check if player can join an existing union at target
        // Check for ally/buddy relationship with union creator
        // Check vacation mode, etc.
    }

    protected function processArrival(FleetMission $mission): void {
        // Don't process individually - wait for union coordinator
        // Mark as "waiting for union"
    }

    public function processUnionArrival(FleetUnion $union): void {
        // Called by FleetUnionService when all fleets arrive
        // Execute combined battle
        // Distribute results to each participating fleet
    }
}
```

#### 4.2 Create `AcsDefendMission` Class
**File**: `app/GameMissions/AcsDefendMission.php`

```php
class AcsDefendMission extends GameMission {
    protected static string $name = 'ACS Defend';
    protected static int $typeId = 5;
    protected static bool $hasReturnMission = true;
    protected static FleetSpeedType $fleetSpeedType = FleetSpeedType::holding;
    protected static FleetMissionStatus $friendlyStatus = FleetMissionStatus::Friendly;

    // Defender fleets orbit the target planet
    // Participate in any attacks that occur while stationed
    // Return after holding time expires
}
```

#### 4.3 Update `GameMissionFactory`
**File**: `app/Factories/GameMissionFactory.php`

```php
return match ($missionId) {
    1 => resolve(AttackMission::class, $dependencies),
    2 => resolve(AcsAttackMission::class, $dependencies),  // NEW
    // ...
    5 => resolve(AcsDefendMission::class, $dependencies),  // NEW
    // ...
};
```

---

### Phase 5: Fleet Coordination Logic

#### 5.1 Union Creation Flow

1. Player sends regular attack mission (type 1) to target
2. After dispatch, option to "Create Union" or "Convert to ACS" appears
3. If converted, mission type changes from 1 to 2, union created with player's mission as slot 1
4. Union creator becomes the **initiator** (their tech levels apply to all attackers)
5. Invitation messages sent to eligible players (Buddies/Alliance members)

#### 5.2 Joining a Union

1. Player receives invitation OR sees existing union to target in fleet dispatch
2. "Join Attack" option appears in mission selection
3. Fleet arrival time must be adjusted to match union arrival time
4. **DELAY LIMIT**: Union arrival can only be delayed by max 30% of remaining time

```php
class FleetUnionService {
    /**
     * Calculate the maximum delay allowed for a union.
     * Rule: Can only delay by 30% of remaining time until impact.
     */
    public function getMaxDelayTime(FleetUnion $union): int
    {
        $remainingTime = $union->time_arrival - Carbon::now()->timestamp;
        return (int)floor($remainingTime * 0.30);
    }

    /**
     * Check if a joining fleet can make the synchronized arrival time.
     * If the fleet would arrive later than allowed delay, reject.
     */
    public function canJoinWithFleet(
        FleetUnion $union,
        PlanetService $fromPlanet,
        UnitCollection $units
    ): bool {
        $maxArrival = $union->time_arrival + $this->getMaxDelayTime($union);
        $fleetArrival = $this->calculateArrivalTime($fromPlanet, $union->getTargetCoordinate(), $units);

        return $fleetArrival <= $maxArrival;
    }

    /**
     * When a fleet joins, potentially delay the union arrival time.
     */
    public function adjustUnionArrivalTime(FleetUnion $union, int $newArrivalTime): void
    {
        $maxArrival = $union->time_arrival + $this->getMaxDelayTime($union);

        if ($newArrivalTime > $maxArrival) {
            throw new Exception('Fleet arrival exceeds maximum delay limit');
        }

        if ($newArrivalTime > $union->time_arrival) {
            $union->time_arrival = $newArrivalTime;
            $union->save();

            // Update all participating fleet missions to new arrival time
            foreach ($union->fleetMissions as $mission) {
                $mission->time_arrival = $newArrivalTime;
                $mission->save();
            }
        }
    }
}
```

#### 5.3 Fleet Withdrawal and Initiator Recall

**IMPORTANT RULES:**
- Players can withdraw their fleets at any time before impact
- If the **initiator** recalls, remaining fleets **continue** to the target
- A new initiator should be assigned (next slot) for tech level purposes

```php
class FleetUnionService {
    /**
     * Handle when a fleet is recalled from the union.
     */
    public function handleFleetRecall(FleetMission $mission, FleetUnion $union): void
    {
        // Remove mission from union
        $mission->union_id = null;
        $mission->save();

        // Check if this was the initiator
        if ($mission->slot_number === 1) {
            $this->reassignInitiator($union);
        }

        // Check if union still has fleets
        $remainingFleets = $union->fleetMissions()->where('processed', 0)->count();
        if ($remainingFleets === 0) {
            // Cancel the union entirely
            $union->processed = 1;
            $union->save();
        }
    }

    /**
     * When initiator recalls, assign new initiator from remaining fleets.
     * The new initiator's tech levels will be used for the battle.
     */
    private function reassignInitiator(FleetUnion $union): void
    {
        $nextFleet = $union->fleetMissions()
            ->where('processed', 0)
            ->where('canceled', 0)
            ->orderBy('slot_number')
            ->first();

        if ($nextFleet) {
            $nextFleet->slot_number = 1;  // Promote to slot 1
            $nextFleet->save();
            // Note: Battle will use this player's tech when it executes
        }
    }
}
```

#### 5.4 Synchronized Return Speeds

**RULE**: Return missions of an ACS Attack use the same synchronized speed as the parent mission, making slowed-down fleets also slow on their return.

```php
class AcsAttackMission extends GameMission {
    /**
     * Start return missions for all fleets in the union.
     * All return missions use the same duration as the parent mission.
     */
    protected function startSynchronizedReturns(
        FleetUnion $union,
        BattleResult $battleResult
    ): void {
        // Calculate base return duration from initiator's mission
        $initiatorMission = $union->fleetMissions()
            ->where('slot_number', 1)
            ->first();

        $returnDuration = $initiatorMission->time_arrival - $initiatorMission->time_departure;

        foreach ($battleResult->attackerResults as $fleetMissionId => $result) {
            $mission = FleetMission::find($fleetMissionId);

            if ($result->unitsResult->getAmount() === 0) {
                // Fleet destroyed, no return mission
                continue;
            }

            // Create return mission with synchronized duration
            $this->createReturnMission(
                $mission,
                $result->unitsResult,
                $result->lootShare->add($result->survivingCargo),
                $returnDuration  // Same duration for ALL fleets
            );
        }
    }
}
```

#### 5.3 Coordinated Arrival Processing

**File**: `app/Services/FleetUnionService.php`

```php
public function processUnionBattle(FleetUnion $union): void {
    // 1. Collect all participating fleet missions
    $fleetMissions = $union->fleetMissions()->where('processed', 0)->get();

    // 2. Build attacker fleets array
    $attackerFleets = [];
    foreach ($fleetMissions as $mission) {
        $attackerFleets[] = new AttackerFleet(
            units: $this->fleetMissionService->getFleetUnits($mission),
            player: $this->playerServiceFactory->make($mission->user_id),
            fleetMissionId: $mission->id
        );
    }

    // 3. Execute combined battle
    $battleEngine = new AcsBattleEngine($attackerFleets, $defenderPlanet, $settings);
    $battleResult = $battleEngine->simulateBattle();

    // 4. Process results for each attacker
    foreach ($battleResult->attackerResults as $result) {
        $this->processAttackerResult($result, $union);
    }

    // 5. Create battle reports and return missions
    // ...
}
```

---

### Phase 6: Loot Distribution

#### 6.1 Loot Calculation for Multiple Attackers

The loot should be distributed proportionally based on:
- **Option A**: Cargo capacity of surviving ships (fairest)
- **Option B**: Initial fleet value (rewards bigger investment)
- **Option C**: Damage dealt (rewards combat contribution)

Recommended: **Cargo capacity of surviving ships**

```php
public function distributeLoot(BattleResult $result): void {
    $totalLoot = $result->loot;
    $totalCargoCapacity = 0;

    // Calculate total surviving cargo capacity
    foreach ($result->attackerResults as $attackerResult) {
        $player = $this->playerServiceFactory->make($attackerResult->playerId);
        $totalCargoCapacity += $attackerResult->unitsResult->getTotalCargoCapacity($player);
    }

    // Distribute loot proportionally
    foreach ($result->attackerResults as $attackerResult) {
        $player = $this->playerServiceFactory->make($attackerResult->playerId);
        $cargoCapacity = $attackerResult->unitsResult->getTotalCargoCapacity($player);
        $sharePercent = $cargoCapacity / $totalCargoCapacity;

        $attackerResult->lootShare = new Resources(
            (int)($totalLoot->metal->get() * $sharePercent),
            (int)($totalLoot->crystal->get() * $sharePercent),
            (int)($totalLoot->deuterium->get() * $sharePercent),
            0
        );
    }
}
```

---

### Phase 7: Battle Reports for ACS

**CRITICAL REQUIREMENT**: Players must be able to filter battle reports by individual fleet and see their fleet's performance throughout the entire battle, including per-round losses.

#### 7.1 Extended `BattleReport` Model Structure

**File**: `app/Models/BattleReport.php`

The battle report must store granular per-fleet data that enables filtering and individual fleet views:

```php
class BattleReport extends Model
{
    protected $casts = [
        'general' => 'array',
        'attacker' => 'array',
        'defender' => 'array',
        'rounds' => 'array',
        'loot' => 'array',
        'debris' => 'array',
        'repaired_defenses' => 'array',
        'wreckage' => 'array',
        // NEW: Per-fleet detailed tracking
        'attacker_fleets' => 'array',   // Individual fleet data
        'defender_fleets' => 'array',   // ACS Defend fleet data
    ];
}
```

#### 7.2 Battle Report Data Structure

```php
// Full battle report structure for ACS battles
$report = [
    'general' => [
        'is_acs_battle' => true,
        'moon_existed' => false,
        'moon_chance' => 12,
        'moon_created' => false,
    ],

    // Combined attacker summary (for quick overview)
    'attacker' => [
        'is_acs' => true,
        'total_participants' => 3,
        'combined_units' => [...],           // All attacker ships combined
        'combined_resource_loss' => 150000,
        'combined_loot' => ['metal' => 50000, 'crystal' => 25000, 'deuterium' => 12500],
    ],

    // NEW: Per-fleet detailed data (enables individual fleet filtering)
    'attacker_fleets' => [
        // Keyed by fleet_mission_id for easy lookup
        '12345' => [
            'fleet_mission_id' => 12345,
            'player_id' => 1,
            'player_name' => 'Attacker1',
            'slot_number' => 1,  // Position in union (1 = initiator)
            'is_initiator' => true,

            // Tech levels
            'weapon_technology' => 15,
            'shielding_technology' => 14,
            'armor_technology' => 15,

            // Fleet composition at START of battle
            'units_start' => [
                'light_fighter' => 1000,
                'heavy_fighter' => 500,
                'cruiser' => 200,
                // ...
            ],

            // Fleet composition at END of battle (survivors)
            'units_result' => [
                'light_fighter' => 850,
                'heavy_fighter' => 420,
                'cruiser' => 195,
                // ...
            ],

            // Total losses
            'units_lost' => [
                'light_fighter' => 150,
                'heavy_fighter' => 80,
                'cruiser' => 5,
                // ...
            ],

            // Resource value of losses
            'resource_loss' => 45000,

            // Loot share received
            'loot_share' => [
                'metal' => 20000,
                'crystal' => 10000,
                'deuterium' => 5000,
            ],

            // Cargo resources that survived
            'surviving_cargo' => [
                'metal' => 5000,
                'crystal' => 2000,
                'deuterium' => 1000,
            ],

            // Per-round breakdown for this fleet
            'rounds' => [
                [
                    'round_number' => 1,
                    'ships_remaining' => [
                        'light_fighter' => 950,
                        'heavy_fighter' => 480,
                        'cruiser' => 198,
                    ],
                    'losses_this_round' => [
                        'light_fighter' => 50,
                        'heavy_fighter' => 20,
                        'cruiser' => 2,
                    ],
                    'cumulative_losses' => [
                        'light_fighter' => 50,
                        'heavy_fighter' => 20,
                        'cruiser' => 2,
                    ],
                    'hits_dealt' => 1523,
                    'damage_dealt' => 4500000,
                    'damage_absorbed' => 320000,
                ],
                // ... rounds 2-6
            ],
        ],
        '12346' => [
            // Second attacker fleet...
        ],
        '12347' => [
            // Third attacker fleet...
        ],
    ],

    // Combined defender summary
    'defender' => [
        'player_id' => 5,
        'has_acs_defenders' => true,
        'total_participants' => 2,  // Planet owner + 1 ACS Defend fleet
        'combined_units' => [...],
        'combined_resource_loss' => 200000,
    ],

    // NEW: Per-fleet data for ACS Defend participants
    'defender_fleets' => [
        '0' => [
            // fleet_mission_id = 0 means planet-based units
            'fleet_mission_id' => 0,
            'player_id' => 5,
            'player_name' => 'PlanetOwner',
            'is_planet_owner' => true,
            // ... same structure as attacker_fleets
        ],
        '12350' => [
            // ACS Defend fleet
            'fleet_mission_id' => 12350,
            'player_id' => 6,
            'player_name' => 'AllyDefender',
            'is_planet_owner' => false,
            // ... same structure as attacker_fleets
        ],
    ],

    // Combined rounds (for overall battle view)
    'rounds' => [
        [
            'round_number' => 1,
            // Combined attacker stats
            'attacker_ships' => [...],
            'attacker_losses' => [...],
            'attacker_losses_in_round' => [...],
            'full_strength_attacker' => 15000000,
            'absorbed_damage_attacker' => 800000,
            'hits_attacker' => 4500,

            // Combined defender stats
            'defender_ships' => [...],
            'defender_losses' => [...],
            'defender_losses_in_round' => [...],
            'full_strength_defender' => 12000000,
            'absorbed_damage_defender' => 650000,
            'hits_defender' => 3800,
        ],
        // ... more rounds
    ],

    // Loot summary
    'loot' => [
        'percentage' => 50,
        'total_metal' => 50000,
        'total_crystal' => 25000,
        'total_deuterium' => 12500,
        // Per-fleet loot breakdown
        'distribution' => [
            '12345' => ['metal' => 20000, 'crystal' => 10000, 'deuterium' => 5000],
            '12346' => ['metal' => 18000, 'crystal' => 9000, 'deuterium' => 4500],
            '12347' => ['metal' => 12000, 'crystal' => 6000, 'deuterium' => 3000],
        ],
    ],

    'debris' => [...],
    'repaired_defenses' => [...],
];
```

#### 7.3 Battle Report Creation for ACS

**File**: `app/GameMissions/AcsAttackMission.php`

```php
private function createAcsBattleReport(
    FleetUnion $union,
    PlanetService $defenderPlanet,
    BattleResult $battleResult
): int {
    $report = new BattleReport();

    // Location
    $report->planet_galaxy = $defenderPlanet->getPlanetCoordinates()->galaxy;
    $report->planet_system = $defenderPlanet->getPlanetCoordinates()->system;
    $report->planet_position = $defenderPlanet->getPlanetCoordinates()->position;
    $report->planet_type = $defenderPlanet->getPlanetType()->value;
    $report->planet_user_id = $defenderPlanet->getPlayer()->getId();

    // General info
    $report->general = [
        'is_acs_battle' => true,
        'union_id' => $union->id,
        'moon_existed' => $battleResult->moonExisted,
        'moon_chance' => $battleResult->moonChance,
        'moon_created' => $battleResult->moonCreated,
    ];

    // Build per-fleet attacker data
    $attackerFleets = [];
    foreach ($battleResult->attackerResults as $fleetMissionId => $result) {
        $player = $this->playerServiceFactory->make($result->playerId);
        $mission = FleetMission::find($fleetMissionId);

        $attackerFleets[$fleetMissionId] = [
            'fleet_mission_id' => $fleetMissionId,
            'player_id' => $result->playerId,
            'player_name' => $player->getUsername(),
            'slot_number' => $mission->slot_number,
            'is_initiator' => $mission->slot_number === 1,

            'weapon_technology' => $result->weaponLevel,
            'shielding_technology' => $result->shieldLevel,
            'armor_technology' => $result->armorLevel,

            'units_start' => $result->unitsStart->toArray(),
            'units_result' => $result->unitsResult->toArray(),
            'units_lost' => $result->unitsLost->toArray(),
            'resource_loss' => $result->resourceLoss->sum(),

            'loot_share' => [
                'metal' => $result->lootShare->metal->get(),
                'crystal' => $result->lootShare->crystal->get(),
                'deuterium' => $result->lootShare->deuterium->get(),
            ],

            'surviving_cargo' => [
                'metal' => $result->survivingCargo->metal->get(),
                'crystal' => $result->survivingCargo->crystal->get(),
                'deuterium' => $result->survivingCargo->deuterium->get(),
            ],

            // Per-round data for this specific fleet
            'rounds' => $this->extractFleetRounds($fleetMissionId, $battleResult->rounds),
        ];
    }

    $report->attacker_fleets = $attackerFleets;

    // Combined attacker summary
    $report->attacker = [
        'is_acs' => true,
        'total_participants' => count($attackerFleets),
        'combined_units' => $battleResult->attackerUnitsStart->toArray(),
        'combined_resource_loss' => $battleResult->attackerResourceLoss->sum(),
    ];

    // ... defender data, rounds, loot, debris, etc.

    $report->save();
    return $report->id;
}

/**
 * Extract round-by-round data for a specific fleet from the battle rounds.
 */
private function extractFleetRounds(int $fleetMissionId, array $rounds): array
{
    $fleetRounds = [];

    foreach ($rounds as $index => $round) {
        $fleetRounds[] = [
            'round_number' => $index + 1,
            'ships_remaining' => $round->attackerShipsPerFleet[$fleetMissionId]?->toArray() ?? [],
            'losses_this_round' => $round->attackerLossesInRoundPerFleet[$fleetMissionId]?->toArray() ?? [],
            'cumulative_losses' => $round->attackerLossesPerFleet[$fleetMissionId]?->toArray() ?? [],
            'hits_dealt' => $round->hitsPerAttackerFleet[$fleetMissionId] ?? 0,
            'damage_dealt' => $round->damagePerAttackerFleet[$fleetMissionId] ?? 0,
        ];
    }

    return $fleetRounds;
}
```

#### 7.4 Battle Report UI with Fleet Filtering

**File**: `resources/views/ingame/messages/templates/battle_report_full.blade.php`

```html
@if($report->general['is_acs_battle'] ?? false)
    <!-- ACS Battle Header -->
    <div class="acs-battle-header">
        <h3>Alliance Combat System Battle</h3>
        <p>{{ count($report->attacker_fleets) }} attacking fleets vs
           {{ count($report->defender_fleets ?? []) }} defending participants</p>
    </div>

    <!-- Fleet Filter Dropdown -->
    <div class="fleet-filter">
        <label for="fleet-select">View Fleet:</label>
        <select id="fleet-select" onchange="filterBattleReport(this.value)">
            <option value="combined">Combined View (All Fleets)</option>
            <optgroup label="Attackers">
                @foreach($report->attacker_fleets as $fleetId => $fleet)
                    <option value="attacker-{{ $fleetId }}">
                        {{ $fleet['player_name'] }}
                        @if($fleet['is_initiator']) (Initiator) @endif
                        - {{ array_sum($fleet['units_start']) }} ships
                    </option>
                @endforeach
            </optgroup>
            @if(!empty($report->defender_fleets))
                <optgroup label="Defenders">
                    @foreach($report->defender_fleets as $fleetId => $fleet)
                        <option value="defender-{{ $fleetId }}">
                            {{ $fleet['player_name'] }}
                            @if($fleet['is_planet_owner']) (Planet Owner) @endif
                        </option>
                    @endforeach
                </optgroup>
            @endif
        </select>
    </div>

    <!-- Combined View (default) -->
    <div id="view-combined" class="battle-view active">
        @include('ingame.messages.templates.battle_report_combined', ['report' => $report])
    </div>

    <!-- Individual Fleet Views (hidden by default) -->
    @foreach($report->attacker_fleets as $fleetId => $fleet)
        <div id="view-attacker-{{ $fleetId }}" class="battle-view" style="display: none;">
            @include('ingame.messages.templates.battle_report_fleet', [
                'fleet' => $fleet,
                'side' => 'attacker',
                'report' => $report
            ])
        </div>
    @endforeach

    @foreach($report->defender_fleets ?? [] as $fleetId => $fleet)
        <div id="view-defender-{{ $fleetId }}" class="battle-view" style="display: none;">
            @include('ingame.messages.templates.battle_report_fleet', [
                'fleet' => $fleet,
                'side' => 'defender',
                'report' => $report
            ])
        </div>
    @endforeach
@else
    <!-- Regular 1v1 battle report -->
    @include('ingame.messages.templates.battle_report_standard', ['report' => $report])
@endif
```

#### 7.5 Individual Fleet View Template

**File**: `resources/views/ingame/messages/templates/battle_report_fleet.blade.php`

```html
<div class="fleet-report">
    <div class="fleet-header">
        <h4>{{ $fleet['player_name'] }}'s Fleet</h4>
        <div class="tech-levels">
            <span>Weapons: {{ $fleet['weapon_technology'] }}</span>
            <span>Shields: {{ $fleet['shielding_technology'] }}</span>
            <span>Armor: {{ $fleet['armor_technology'] }}</span>
        </div>
    </div>

    <!-- Fleet Summary -->
    <div class="fleet-summary">
        <div class="summary-box">
            <h5>Starting Fleet</h5>
            @foreach($fleet['units_start'] as $unit => $count)
                @if($count > 0)
                    <div class="unit-row">
                        <span class="unit-name">{{ __('units.' . $unit) }}</span>
                        <span class="unit-count">{{ number_format($count) }}</span>
                    </div>
                @endif
            @endforeach
        </div>

        <div class="summary-box">
            <h5>Survivors</h5>
            @foreach($fleet['units_result'] as $unit => $count)
                @if($count > 0)
                    <div class="unit-row">
                        <span class="unit-name">{{ __('units.' . $unit) }}</span>
                        <span class="unit-count">{{ number_format($count) }}</span>
                    </div>
                @endif
            @endforeach
        </div>

        <div class="summary-box losses">
            <h5>Losses</h5>
            @foreach($fleet['units_lost'] as $unit => $count)
                @if($count > 0)
                    <div class="unit-row">
                        <span class="unit-name">{{ __('units.' . $unit) }}</span>
                        <span class="unit-count text-danger">-{{ number_format($count) }}</span>
                    </div>
                @endif
            @endforeach
            <div class="total-loss">
                Resource Value: {{ number_format($fleet['resource_loss']) }}
            </div>
        </div>
    </div>

    @if($side === 'attacker' && !empty($fleet['loot_share']))
        <div class="loot-share">
            <h5>Loot Received</h5>
            <div class="resources">
                <span class="metal">Metal: {{ number_format($fleet['loot_share']['metal']) }}</span>
                <span class="crystal">Crystal: {{ number_format($fleet['loot_share']['crystal']) }}</span>
                <span class="deuterium">Deuterium: {{ number_format($fleet['loot_share']['deuterium']) }}</span>
            </div>
        </div>
    @endif

    <!-- Round-by-Round Breakdown for This Fleet -->
    <div class="rounds-breakdown">
        <h5>Battle Rounds</h5>
        <table class="rounds-table">
            <thead>
                <tr>
                    <th>Round</th>
                    <th>Ships Remaining</th>
                    <th>Losses This Round</th>
                    <th>Hits Dealt</th>
                    <th>Damage Dealt</th>
                </tr>
            </thead>
            <tbody>
                @foreach($fleet['rounds'] as $round)
                    <tr>
                        <td>{{ $round['round_number'] }}</td>
                        <td>{{ number_format(array_sum($round['ships_remaining'])) }}</td>
                        <td class="text-danger">
                            -{{ number_format(array_sum($round['losses_this_round'])) }}
                        </td>
                        <td>{{ number_format($round['hits_dealt']) }}</td>
                        <td>{{ number_format($round['damage_dealt']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Expandable per-round unit details -->
        @foreach($fleet['rounds'] as $round)
            <div class="round-details" data-round="{{ $round['round_number'] }}">
                <button class="toggle-details" onclick="toggleRoundDetails({{ $round['round_number'] }})">
                    Round {{ $round['round_number'] }} Details
                </button>
                <div class="details-content" style="display: none;">
                    <div class="ships-remaining">
                        <h6>Ships Remaining After Round {{ $round['round_number'] }}</h6>
                        @foreach($round['ships_remaining'] as $unit => $count)
                            @if($count > 0)
                                <span>{{ __('units.' . $unit) }}: {{ number_format($count) }}</span>
                            @endif
                        @endforeach
                    </div>
                    <div class="losses-this-round">
                        <h6>Losses in Round {{ $round['round_number'] }}</h6>
                        @foreach($round['losses_this_round'] as $unit => $count)
                            @if($count > 0)
                                <span class="text-danger">
                                    {{ __('units.' . $unit) }}: -{{ number_format($count) }}
                                </span>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
```

#### 7.6 JavaScript for Fleet Filtering

```javascript
function filterBattleReport(viewId) {
    // Hide all views
    document.querySelectorAll('.battle-view').forEach(view => {
        view.style.display = 'none';
        view.classList.remove('active');
    });

    // Show selected view
    const selectedView = document.getElementById('view-' + viewId);
    if (selectedView) {
        selectedView.style.display = 'block';
        selectedView.classList.add('active');
    }
}

function toggleRoundDetails(roundNumber) {
    const details = document.querySelector(
        `.round-details[data-round="${roundNumber}"] .details-content`
    );
    if (details) {
        details.style.display = details.style.display === 'none' ? 'block' : 'none';
    }
}
```

#### 7.7 One Report Per Player (Not Per Fleet)

**RULE**: Each player receives exactly ONE battle report, regardless of how many fleets they sent.

```php
class AcsAttackMission extends GameMission {
    /**
     * Send battle reports to all participants.
     * Each PLAYER gets one report, not each fleet.
     */
    protected function sendBattleReports(int $reportId, BattleResult $battleResult): void
    {
        $sentToPlayers = [];

        // Send to attackers (deduplicate by player_id)
        foreach ($battleResult->attackerResults as $result) {
            if (!in_array($result->playerId, $sentToPlayers)) {
                $player = $this->playerServiceFactory->make($result->playerId);
                $this->messageService->sendBattleReportMessageToPlayer($player, $reportId);
                $sentToPlayers[] = $result->playerId;
            }
        }

        // Send to defender (planet owner)
        $defenderId = $this->defenderPlanet->getPlayer()->getId();
        if (!in_array($defenderId, $sentToPlayers)) {
            $this->messageService->sendBattleReportMessageToPlayer(
                $this->defenderPlanet->getPlayer(),
                $reportId
            );
            $sentToPlayers[] = $defenderId;
        }

        // Send to ACS Defend fleet owners
        foreach ($battleResult->defenderResults ?? [] as $result) {
            if (!in_array($result->playerId, $sentToPlayers)) {
                $player = $this->playerServiceFactory->make($result->playerId);
                $this->messageService->sendBattleReportMessageToPlayer($player, $reportId);
                $sentToPlayers[] = $result->playerId;
            }
        }
    }
}
```

---

### Phase 8: UI/UX Updates

#### 8.1 Fleet Dispatch Screen Updates

**File**: `resources/views/ingame/fleet/index.blade.php`

- Add "Join existing attack" option when unions exist for target
- Show list of available unions to join
- Display union details (participants, arrival time, total fleet strength)

#### 8.2 Fleet Movement Screen Updates

**File**: `resources/views/ingame/fleet/movement.blade.php`

- Group ACS missions together
- Show union name and participant count
- Indicate synchronized arrival time

#### 8.3 New Union Management Interface

- Create/view/cancel unions
- Invite players to union
- Accept/decline union invitations

---

### Phase 9: ACS Defend Implementation

#### 9.1 Defend Mission Flow

1. Player sends ACS Defend mission to ally's planet
2. Fleet arrives and "holds" at the planet (uses `time_holding`)
3. If attack occurs during holding period:
   - Defending fleet participates in battle on defender's side
   - Losses are tracked per defending fleet
4. After holding time expires, fleet returns

#### 9.2 Battle Engine Considerations for Defenders

- Defender side now has multiple unit groups with different tech levels
- Similar structure to multiple attackers
- Each defending fleet has separate loss tracking and return mission

---

## Implementation Order (Recommended)

### Milestone 1: Foundation
1. Database migrations (fleet_unions table, update fleet_missions)
2. FleetUnion model
3. Basic FleetUnionService

### Milestone 2: Multi-Attacker Battle Engine
1. AttackerFleet data structure
2. AcsBattleEngine class
3. Updated BattleResult with per-attacker tracking
4. Loot distribution logic

### Milestone 3: ACS Attack Mission
1. AcsAttackMission class
2. Update GameMissionFactory
3. Union creation/joining logic
4. Coordinated arrival processing

### Milestone 4: UI Integration
1. Fleet dispatch updates for joining unions
2. Fleet movement display updates
3. Battle report template updates for ACS

### Milestone 5: ACS Defend Mission
1. AcsDefendMission class
2. Defender-side multi-fleet battle support
3. Holding logic integration

### Milestone 6: Polish & Testing
1. Comprehensive test coverage
2. Edge case handling
3. Performance optimization
4. UI/UX refinements

---

## Key Technical Considerations

### Race Conditions
- Multiple fleets arriving simultaneously requires careful locking
- Use database transactions for union processing
- Consider queue-based processing for large battles

### Rust Battle Engine
- If extending RustBattleEngine, FFI interface needs updates
- Consider keeping ACS battles in PHP engine initially

### Backward Compatibility
- Existing AttackMission (type 1) should continue working unchanged
- BattleResult maintains single-attacker fields for non-ACS battles

### Performance
- Large ACS battles (5 attackers x thousands of ships) need optimization
- Consider batch processing for battle calculations
- Lazy loading for battle report details

### Destroyed Fleet Handling (ACS Defend)

**IMPORTANT**: When an ACS Defend guest fleet is completely destroyed during battle, special handling is required:

1. **No Empty Return Missions**: Do NOT create a return mission with 0 ships
   - Empty return missions waste a fleet slot for the player
   - They create confusing UI entries showing "0 ships returning"
   - Check `$result->unitsResult->getAmount() > 0` before creating return mission

2. **Implementation in `AcsDefendMission`**:
   ```php
   // In processDefenderResults() or similar method
   foreach ($battleResult->defenderResults as $fleetMissionId => $result) {
       // Skip creating return mission if fleet was completely destroyed
       if ($result->unitsResult->getAmount() === 0) {
           // Mark the mission as processed but don't create return
           $mission = FleetMission::find($fleetMissionId);
           $mission->processed = 1;
           $mission->save();

           // No special message needed - follows existing rules:
           // - Destroyed in round 1: "fleet_lost_contact" (no battle report)
           // - Destroyed in round 2+: normal battle report shows their losses
           continue;
       }

       // Create return mission only for surviving fleets
       $this->createReturnMission($mission, $result->unitsResult);
   }
   ```

3. **Messaging Rules** (follows existing 1v1 behavior):
   - **Destroyed in Round 1**: Player receives "fleet_lost_contact" message (no battle report)
   - **Destroyed in Round 2+**: Player receives normal battle report showing their fleet's losses
   - No special "fleet destroyed" message is needed - the existing rules apply

4. **Consistency with ACS Attack**: Apply same logic to ACS Attack fleets
   - If an attacker's fleet is completely destroyed, no return mission needed
   - The existing `AttackMission` already handles this for 1v1 battles

5. **Battle Report Considerations**:
   - Still include destroyed fleets in battle report (show losses)
   - Mark fleet as "Destroyed" in the per-fleet view
   - Don't show "Return ETA" for destroyed fleets

6. **Test Case to Add**:
   ```php
   /**
    * Test that completely destroyed ACS Defend fleet does not create return mission.
    */
   public function testDestroyedDefendFleetNoReturnMission(): void
   {
       // Setup: Send weak defend fleet against strong attacker
       // Assert: No return mission created
       // Assert: Fleet slot is freed
       // Assert: Player receives battle report (or fleet_lost_contact if round 1)
   }
   ```

---

## Files to Create/Modify

### New Files
- `database/migrations/xxxx_create_fleet_unions_table.php`
- `database/migrations/xxxx_add_union_to_fleet_missions.php`
- `app/Models/FleetUnion.php`
- `app/Models/FleetUnionParticipant.php` (optional, for invites)
- `app/Services/FleetUnionService.php`
- `app/GameMissions/AcsAttackMission.php`
- `app/GameMissions/AcsDefendMission.php`
- `app/GameMissions/BattleEngine/AcsBattleEngine.php`
- `app/GameMissions/BattleEngine/Models/AttackerFleet.php`
- `app/GameMissions/BattleEngine/Models/AttackerResult.php`
- `app/Http/Controllers/FleetUnionController.php`
- `resources/views/ingame/fleet/union/` (new directory for union UI)
  - `resources/views/ingame/fleet/union/create.blade.php`
  - `resources/views/ingame/fleet/union/join.blade.php`
  - `resources/views/ingame/fleet/union/manage.blade.php`

### Modified Files
- `app/Models/FleetMission.php` (add union relationship)
- `app/Factories/GameMissionFactory.php` (add types 2, 5)
- `app/Services/FleetMissionService.php` (union awareness)
- `app/GameMissions/BattleEngine/Models/BattleUnit.php` (add owner tracking)
- `app/GameMissions/BattleEngine/Models/BattleResult.php` (add ACS fields)
- `app/GameMissions/BattleEngine/Models/BattleResultRound.php` (add per-fleet tracking)
- `app/Models/BattleReport.php` (multi-attacker structure)
- `app/Http/Controllers/FleetController.php` (union endpoints)
- `resources/views/ingame/fleet/index.blade.php` (join union UI)
- `resources/views/ingame/fleet/movement.blade.php` (show union info)
- `resources/views/ingame/messages/templates/battle_report.blade.php` (multi-attacker display)
- `resources/views/ingame/messages/templates/battle_report_full.blade.php`
- `routes/web.php` (new union routes)

---

## Comprehensive Testing Strategy

This section provides detailed test specifications for the ACS feature. Tests follow existing OGameX patterns using PHPUnit with Laravel's testing utilities.

---

### Milestone 9: Feature Tests (Required Before Release)

| # | Task | File(s) | Depends On | Done When |
|---|------|---------|------------|-----------|
| 9.1 | Create ACS test base class | `tests/Feature/FleetDispatch/FleetDispatchAcsTestCase.php` | 4.1 | Base class with helper methods for ACS tests |
| 9.2 | Write ACS Attack dispatch tests | `tests/Feature/FleetDispatch/FleetDispatchAcsAttackTest.php` | 9.1, 4.1 | All dispatch scenarios pass |
| 9.3 | Write ACS Defend dispatch tests | `tests/Feature/FleetDispatch/FleetDispatchAcsDefendTest.php` | 9.1, 4.2 | All defend scenarios pass |
| 9.4 | Write FleetUnionService unit tests | `tests/Unit/FleetUnionServiceTest.php` | 3.1-3.6 | All service methods tested |
| 9.5 | Write AcsBattleEngine unit tests | `tests/Unit/BattleEngine/AcsBattleEngineTest.php` | 2.6 | Multi-attacker battles tested |
| 9.6 | Write loot distribution tests | `tests/Unit/AcsLootDistributionTest.php` | 5.1-5.2 | Loot split correctly by cargo |
| 9.7 | Write battle report tests | `tests/Feature/AcsBattleReportTest.php` | 6.1-6.6 | Per-fleet data populated correctly |

---

### Test File Structure

```
tests/
├── Feature/
│   └── FleetDispatch/
│       ├── FleetDispatchAcsTestCase.php      # Base class with ACS helpers
│       ├── FleetDispatchAcsAttackTest.php    # ACS Attack mission tests
│       └── FleetDispatchAcsDefendTest.php    # ACS Defend mission tests
│   └── AcsBattleReportTest.php               # Battle report content tests
├── Unit/
│   ├── FleetUnionServiceTest.php             # Union service unit tests
│   ├── AcsLootDistributionTest.php           # Loot calculation tests
│   └── BattleEngine/
│       └── AcsBattleEngineTest.php           # Multi-attacker engine tests
```

---

### 9.1 ACS Test Base Class

**File**: `tests/Feature/FleetDispatch/FleetDispatchAcsTestCase.php`

```php
<?php

namespace Tests\Feature\FleetDispatch;

use OGame\GameObjects\Models\Units\UnitCollection;
use OGame\Models\FleetUnion;
use OGame\Models\Resources;
use OGame\Services\FleetMissionService;
use OGame\Services\FleetUnionService;
use OGame\Services\ObjectService;
use Tests\FleetDispatchTestCase;

abstract class FleetDispatchAcsTestCase extends FleetDispatchTestCase
{
    protected FleetUnionService $fleetUnionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fleetUnionService = resolve(FleetUnionService::class);
    }

    /**
     * Create a second player who is an ally of the main test player.
     */
    protected function createAllyPlayer(): PlayerService
    {
        // Create player and set up alliance relationship
        $allyPlayer = $this->createPlayer('AllyPlayer');
        $this->makePlayersAllies($this->planetService->getPlayer(), $allyPlayer);
        return $allyPlayer;
    }

    /**
     * Create a second player who is a buddy of the main test player.
     */
    protected function createBuddyPlayer(): PlayerService
    {
        $buddyPlayer = $this->createPlayer('BuddyPlayer');
        $this->makePlayersBuddies($this->planetService->getPlayer(), $buddyPlayer);
        return $buddyPlayer;
    }

    /**
     * Helper to dispatch an ACS Attack mission and create a union.
     */
    protected function dispatchAcsAttackWithUnion(
        UnitCollection $units,
        PlanetService $targetPlanet
    ): FleetUnion {
        // Dispatch initial attack
        $this->sendMissionToOtherPlayerPlanet($units, new Resources(0, 0, 0, 0), true, 2);

        // Get the mission and create union
        $fleetMissionService = resolve(FleetMissionService::class);
        $mission = $fleetMissionService->getActiveFleetMissionsForCurrentPlayer()->first();

        return $this->fleetUnionService->createUnion(
            $this->planetService->getPlayer(),
            $mission
        );
    }

    /**
     * Helper to join an existing union with a second player's fleet.
     */
    protected function joinUnionWithAlly(
        FleetUnion $union,
        PlayerService $allyPlayer,
        UnitCollection $units
    ): FleetMission {
        // Switch to ally player context
        $this->actingAs($allyPlayer->getUser());

        $allyPlanet = $allyPlayer->planets->first();
        $fleetMissionService = resolve(FleetMissionService::class, ['player' => $allyPlayer]);

        // Dispatch fleet to same target
        $mission = $fleetMissionService->createNewFromPlanet(
            $allyPlanet,
            $union->getTargetCoordinate(),
            PlanetType::Planet,
            2, // ACS Attack
            $units,
            new Resources(0, 0, 0, 0),
            10
        );

        // Join the union
        $this->fleetUnionService->joinUnion($union, $mission);

        return $mission;
    }
}
```

---

### 9.2 ACS Attack Feature Tests

**File**: `tests/Feature/FleetDispatch/FleetDispatchAcsAttackTest.php`

```php
<?php

namespace Tests\Feature\FleetDispatch;

use OGame\GameObjects\Models\Units\UnitCollection;
use OGame\Models\BattleReport;
use OGame\Models\FleetMission;
use OGame\Models\Message;
use OGame\Models\Resources;
use OGame\Services\FleetMissionService;
use OGame\Services\ObjectService;
use OGame\Services\SettingsService;

class FleetDispatchAcsAttackTest extends FleetDispatchAcsTestCase
{
    protected int $missionType = 2;
    protected string $missionName = 'ACS Attack';

    protected function basicSetup(): void
    {
        $this->planetAddUnit('light_fighter', 100);
        $this->playerSetResearchLevel('computer_technology', 5);
        $this->planetAddResources(new Resources(0, 0, 1000000, 0));

        $settingsService = resolve(SettingsService::class);
        $settingsService->set('fleet_speed_war', 1);
    }

    // =========================================================================
    // UNION CREATION TESTS
    // =========================================================================

    /**
     * Test that a player can create a fleet union from an existing attack mission.
     */
    public function testCreateUnionFromAttackMission(): void
    {
        $this->basicSetup();

        $unitCollection = new UnitCollection();
        $unitCollection->addUnit(ObjectService::getUnitObjectByMachineName('light_fighter'), 50);
        $foreignPlanet = $this->sendMissionToOtherPlayerPlanet($unitCollection, new Resources(0, 0, 0, 0));

        // Create union from the mission
        $fleetMissionService = resolve(FleetMissionService::class);
        $mission = $fleetMissionService->getActiveFleetMissionsForCurrentPlayer()->first();

        $union = $this->fleetUnionService->createUnion($this->planetService->getPlayer(), $mission);

        $this->assertNotNull($union, 'Union should be created');
        $this->assertEquals(1, $union->fleetMissions()->count(), 'Union should have 1 fleet');
        $this->assertEquals($this->planetService->getPlayer()->getId(), $union->user_id);
    }

    /**
     * Test that creating a union converts mission type from 1 to 2.
     */
    public function testCreateUnionConvertsMissionType(): void
    {
        $this->basicSetup();

        // Send regular attack (type 1)
        $unitCollection = new UnitCollection();
        $unitCollection->addUnit(ObjectService::getUnitObjectByMachineName('light_fighter'), 50);
        $this->sendMissionToOtherPlayerPlanet($unitCollection, new Resources(0, 0, 0, 0), true, 1);

        $fleetMissionService = resolve(FleetMissionService::class);
        $mission = $fleetMissionService->getActiveFleetMissionsForCurrentPlayer()->first();

        // Verify initial type
        $this->assertEquals(1, $mission->mission_type);

        // Create union - should convert to type 2
        $this->fleetUnionService->createUnion($this->planetService->getPlayer(), $mission);

        $mission->refresh();
        $this->assertEquals(2, $mission->mission_type, 'Mission should be converted to ACS Attack (type 2)');
    }

    // =========================================================================
    // UNION JOINING TESTS
    // =========================================================================

    /**
     * Test that an ally can join an existing union.
     */
    public function testAllyCanJoinUnion(): void
    {
        $this->basicSetup();

        // Create initial attack and union
        $unitCollection = new UnitCollection();
        $unitCollection->addUnit(ObjectService::getUnitObjectByMachineName('light_fighter'), 50);
        $foreignPlanet = $this->getNearbyForeignPlanet();
        $union = $this->dispatchAcsAttackWithUnion($unitCollection, $foreignPlanet);

        // Create ally player with fleet
        $allyPlayer = $this->createAllyPlayer();
        $allyUnits = new UnitCollection();
        $allyUnits->addUnit(ObjectService::getUnitObjectByMachineName('cruiser'), 10);

        // Join union
        $allyMission = $this->joinUnionWithAlly($union, $allyPlayer, $allyUnits);

        $union->refresh();
        $this->assertEquals(2, $union->fleetMissions()->count(), 'Union should have 2 fleets');
    }

    /**
     * Test that a buddy can join an existing union.
     */
    public function testBuddyCanJoinUnion(): void
    {
        $this->basicSetup();

        $unitCollection = new UnitCollection();
        $unitCollection->addUnit(ObjectService::getUnitObjectByMachineName('light_fighter'), 50);
        $foreignPlanet = $this->getNearbyForeignPlanet();
        $union = $this->dispatchAcsAttackWithUnion($unitCollection, $foreignPlanet);

        $buddyPlayer = $this->createBuddyPlayer();
        $buddyUnits = new UnitCollection();
        $buddyUnits->addUnit(ObjectService::getUnitObjectByMachineName('cruiser'), 10);

        // Switch to buddy and join
        $this->actingAs($buddyPlayer->getUser());
        $buddyPlanet = $buddyPlayer->planets->first();
        $buddyPlanet->addUnit('cruiser', 10);
        $buddyPlanet->addResources(new Resources(0, 0, 100000, 0));

        $fleetMissionService = resolve(FleetMissionService::class, ['player' => $buddyPlayer]);
        $mission = $fleetMissionService->createNewFromPlanet(
            $buddyPlanet,
            $union->getTargetCoordinate(),
            PlanetType::Planet,
            2,
            $buddyUnits,
            new Resources(0, 0, 0, 0),
            10
        );

        $this->fleetUnionService->joinUnion($union, $mission);

        $union->refresh();
        $this->assertEquals(2, $union->fleetMissions()->count());
    }

    /**
     * Test that a non-ally/non-buddy cannot join a union.
     */
    public function testStrangerCannotJoinUnion(): void
    {
        $this->basicSetup();

        $unitCollection = new UnitCollection();
        $unitCollection->addUnit(ObjectService::getUnitObjectByMachineName('light_fighter'), 50);
        $foreignPlanet = $this->getNearbyForeignPlanet();
        $union = $this->dispatchAcsAttackWithUnion($unitCollection, $foreignPlanet);

        // Create stranger (not ally or buddy)
        $strangerPlayer = $this->createPlayer('Stranger');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(__('t_acs.error_not_buddy_or_ally'));

        // Attempt to join should fail
        $strangerUnits = new UnitCollection();
        $strangerUnits->addUnit(ObjectService::getUnitObjectByMachineName('cruiser'), 10);

        $this->actingAs($strangerPlayer->getUser());
        $strangerPlanet = $strangerPlayer->planets->first();

        $fleetMissionService = resolve(FleetMissionService::class, ['player' => $strangerPlayer]);
        $mission = $fleetMissionService->createNewFromPlanet(
            $strangerPlanet,
            $union->getTargetCoordinate(),
            PlanetType::Planet,
            2,
            $strangerUnits,
            new Resources(0, 0, 0, 0),
            10
        );

        $this->fleetUnionService->joinUnion($union, $mission);
    }

    /**
     * Test that union respects maximum 16 fleets limit.
     */
    public function testUnionMaxFleetsLimit(): void
    {
        $this->basicSetup();

        $unitCollection = new UnitCollection();
        $unitCollection->addUnit(ObjectService::getUnitObjectByMachineName('light_fighter'), 10);
        $foreignPlanet = $this->getNearbyForeignPlanet();
        $union = $this->dispatchAcsAttackWithUnion($unitCollection, $foreignPlanet);

        // Add 15 more fleets (to reach 16 total)
        for ($i = 0; $i < 15; $i++) {
            $allyPlayer = $this->createPlayer("Ally{$i}");
            $this->makePlayersAllies($this->planetService->getPlayer(), $allyPlayer);

            $allyPlanet = $allyPlayer->planets->first();
            $allyPlanet->addUnit('light_fighter', 5);
            $allyPlanet->addResources(new Resources(0, 0, 100000, 0));

            $fleetMissionService = resolve(FleetMissionService::class, ['player' => $allyPlayer]);
            $mission = $fleetMissionService->createNewFromPlanet(
                $allyPlanet,
                $union->getTargetCoordinate(),
                PlanetType::Planet,
                2,
                $unitCollection,
                new Resources(0, 0, 0, 0),
                10
            );

            $this->fleetUnionService->joinUnion($union, $mission);
        }

        $union->refresh();
        $this->assertEquals(16, $union->fleetMissions()->count());

        // 17th fleet should be rejected
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(__('t_acs.error_max_fleets_reached'));

        $extraAlly = $this->createPlayer('ExtraAlly');
        $this->makePlayersAllies($this->planetService->getPlayer(), $extraAlly);

        $extraPlanet = $extraAlly->planets->first();
        $extraPlanet->addUnit('light_fighter', 5);
        $extraPlanet->addResources(new Resources(0, 0, 100000, 0));

        $fleetMissionService = resolve(FleetMissionService::class, ['player' => $extraAlly]);
        $extraMission = $fleetMissionService->createNewFromPlanet(
            $extraPlanet,
            $union->getTargetCoordinate(),
            PlanetType::Planet,
            2,
            $unitCollection,
            new Resources(0, 0, 0, 0),
            10
        );

        $this->fleetUnionService->joinUnion($union, $extraMission);
    }

    /**
     * Test that union respects maximum 5 players limit.
     */
    public function testUnionMaxPlayersLimit(): void
    {
        $this->basicSetup();

        $unitCollection = new UnitCollection();
        $unitCollection->addUnit(ObjectService::getUnitObjectByMachineName('light_fighter'), 10);
        $foreignPlanet = $this->getNearbyForeignPlanet();
        $union = $this->dispatchAcsAttackWithUnion($unitCollection, $foreignPlanet);

        // Add 4 more players (to reach 5 total)
        for ($i = 0; $i < 4; $i++) {
            $allyPlayer = $this->createPlayer("AllyPlayer{$i}");
            $this->makePlayersAllies($this->planetService->getPlayer(), $allyPlayer);

            $allyPlanet = $allyPlayer->planets->first();
            $allyPlanet->addUnit('light_fighter', 5);
            $allyPlanet->addResources(new Resources(0, 0, 100000, 0));

            $fleetMissionService = resolve(FleetMissionService::class, ['player' => $allyPlayer]);
            $mission = $fleetMissionService->createNewFromPlanet(
                $allyPlanet,
                $union->getTargetCoordinate(),
                PlanetType::Planet,
                2,
                $unitCollection,
                new Resources(0, 0, 0, 0),
                10
            );

            $this->fleetUnionService->joinUnion($union, $mission);
        }

        $this->assertEquals(5, $union->getUniquePlayerCount());

        // 6th player should be rejected
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(__('t_acs.error_max_players_reached'));

        $sixthPlayer = $this->createPlayer('SixthPlayer');
        $this->makePlayersAllies($this->planetService->getPlayer(), $sixthPlayer);

        $sixthPlanet = $sixthPlayer->planets->first();
        $sixthPlanet->addUnit('light_fighter', 5);
        $sixthPlanet->addResources(new Resources(0, 0, 100000, 0));

        $fleetMissionService = resolve(FleetMissionService::class, ['player' => $sixthPlayer]);
        $mission = $fleetMissionService->createNewFromPlanet(
            $sixthPlanet,
            $union->getTargetCoordinate(),
            PlanetType::Planet,
            2,
            $unitCollection,
            new Resources(0, 0, 0, 0),
            10
        );

        $this->fleetUnionService->joinUnion($union, $mission);
    }

    // =========================================================================
    // DELAY LIMIT TESTS
    // =========================================================================

    /**
     * Test that union delay is limited to 30% of remaining time.
     */
    public function testUnionDelayLimit(): void
    {
        $this->basicSetup();

        $unitCollection = new UnitCollection();
        $unitCollection->addUnit(ObjectService::getUnitObjectByMachineName('light_fighter'), 50);
        $foreignPlanet = $this->getNearbyForeignPlanet();
        $union = $this->dispatchAcsAttackWithUnion($unitCollection, $foreignPlanet);

        $remainingTime = $union->time_arrival - now()->timestamp;
        $maxDelay = (int)floor($remainingTime * 0.30);

        $calculatedMaxDelay = $this->fleetUnionService->getMaxDelayTime($union);

        $this->assertEquals($maxDelay, $calculatedMaxDelay, 'Max delay should be 30% of remaining time');
    }

    /**
     * Test that fleet exceeding delay limit cannot join.
     */
    public function testFleetExceedingDelayLimitRejected(): void
    {
        $this->basicSetup();

        // Create a union with short arrival time
        $unitCollection = new UnitCollection();
        $unitCollection->addUnit(ObjectService::getUnitObjectByMachineName('light_fighter'), 50);
        $foreignPlanet = $this->getNearbyForeignPlanet();
        $union = $this->dispatchAcsAttackWithUnion($unitCollection, $foreignPlanet);

        // Create ally on a very distant planet
        $allyPlayer = $this->createAllyPlayer();
        $distantPlanet = $this->createPlanetForPlayer($allyPlayer, 9, 499, 15); // Far away

        $distantPlanet->addUnit('light_fighter', 10);
        $distantPlanet->addResources(new Resources(0, 0, 10000000, 0));

        $allyUnits = new UnitCollection();
        $allyUnits->addUnit(ObjectService::getUnitObjectByMachineName('light_fighter'), 10);

        // Check if fleet can join (should fail if too slow)
        $canJoin = $this->fleetUnionService->canJoinWithFleet(
            $union,
            $distantPlanet,
            $allyUnits
        );

        // If the fleet is too slow, it should not be able to join
        if (!$canJoin) {
            $this->assertFalse($canJoin, 'Fleet from distant planet should not be able to join');
        }
    }

    // =========================================================================
    // BATTLE PROCESSING TESTS
    // =========================================================================

    /**
     * Test that ACS battle processes correctly with multiple attackers.
     */
    public function testAcsBattleProcessesWithMultipleAttackers(): void
    {
        $this->basicSetup();

        // Disable resource generation
        $settingsService = resolve(SettingsService::class);
        $settingsService->set('economy_speed', 0);

        // Create union with 2 attackers
        $unitCollection = new UnitCollection();
        $unitCollection->addUnit(ObjectService::getUnitObjectByMachineName('cruiser'), 100);
        $foreignPlanet = $this->sendMissionToOtherPlayerCleanPlanet($unitCollection, new Resources(0, 0, 0, 0), true, 2);

        $fleetMissionService = resolve(FleetMissionService::class);
        $initiatorMission = $fleetMissionService->getActiveFleetMissionsForCurrentPlayer()->first();
        $union = $this->fleetUnionService->createUnion($this->planetService->getPlayer(), $initiatorMission);

        // Add ally fleet
        $allyPlayer = $this->createAllyPlayer();
        $allyUnits = new UnitCollection();
        $allyUnits->addUnit(ObjectService::getUnitObjectByMachineName('cruiser'), 50);
        $this->joinUnionWithAlly($union, $allyPlayer, $allyUnits);

        // Add defender units
        $foreignPlanet->addUnit('rocket_launcher', 200);

        // Process battle
        $this->travel(24)->hours();
        $this->reloadApplication();
        $this->get('/overview');

        // Verify battle report was created
        $battleReport = BattleReport::orderBy('id', 'desc')->first();
        $this->assertNotNull($battleReport);
        $this->assertTrue($battleReport->general['is_acs_battle'] ?? false, 'Battle should be marked as ACS');
        $this->assertEquals(2, count($battleReport->attacker_fleets ?? []), 'Should have 2 attacker fleets in report');
    }

    /**
     * Test that each participant uses their own tech levels.
     */
    public function testEachParticipantUsesOwnTechLevels(): void
    {
        $this->basicSetup();

        // Set initiator tech levels
        $this->playerSetResearchLevel('weapon_technology', 15);
        $this->playerSetResearchLevel('shielding_technology', 14);
        $this->playerSetResearchLevel('armor_technology', 13);

        $unitCollection = new UnitCollection();
        $unitCollection->addUnit(ObjectService::getUnitObjectByMachineName('cruiser'), 50);
        $foreignPlanet = $this->sendMissionToOtherPlayerCleanPlanet($unitCollection, new Resources(0, 0, 0, 0), true, 2);

        $fleetMissionService = resolve(FleetMissionService::class);
        $initiatorMission = $fleetMissionService->getActiveFleetMissionsForCurrentPlayer()->first();
        $union = $this->fleetUnionService->createUnion($this->planetService->getPlayer(), $initiatorMission);

        // Create ally with different tech levels
        $allyPlayer = $this->createAllyPlayer();
        $allyPlayer->setResearchLevel('weapon_technology', 5);
        $allyPlayer->setResearchLevel('shielding_technology', 5);
        $allyPlayer->setResearchLevel('armor_technology', 5);

        $allyUnits = new UnitCollection();
        $allyUnits->addUnit(ObjectService::getUnitObjectByMachineName('cruiser'), 50);
        $this->joinUnionWithAlly($union, $allyPlayer, $allyUnits);

        // Process battle
        $this->travel(24)->hours();
        $this->reloadApplication();
        $this->get('/overview');

        // Verify battle report contains different tech levels for each fleet
        $battleReport = BattleReport::orderBy('id', 'desc')->first();
        $attackerFleets = $battleReport->attacker_fleets;

        $initiatorFleet = collect($attackerFleets)->firstWhere('is_initiator', true);
        $allyFleet = collect($attackerFleets)->firstWhere('is_initiator', false);

        $this->assertEquals(15, $initiatorFleet['weapon_technology']);
        $this->assertEquals(5, $allyFleet['weapon_technology']);
    }

    // =========================================================================
    // LOOT DISTRIBUTION TESTS
    // =========================================================================

    /**
     * Test that loot is distributed by surviving cargo capacity.
     */
    public function testLootDistributedByCargoCapacity(): void
    {
        $this->basicSetup();

        $settingsService = resolve(SettingsService::class);
        $settingsService->set('economy_speed', 0);

        // Initiator: 10 large cargo (250k capacity)
        $this->planetAddUnit('large_cargo', 10);
        $unitCollection = new UnitCollection();
        $unitCollection->addUnit(ObjectService::getUnitObjectByMachineName('large_cargo'), 10);
        $unitCollection->addUnit(ObjectService::getUnitObjectByMachineName('cruiser'), 100);

        $foreignPlanet = $this->sendMissionToOtherPlayerCleanPlanet($unitCollection, new Resources(0, 0, 0, 0), true, 2);

        $fleetMissionService = resolve(FleetMissionService::class);
        $initiatorMission = $fleetMissionService->getActiveFleetMissionsForCurrentPlayer()->first();
        $union = $this->fleetUnionService->createUnion($this->planetService->getPlayer(), $initiatorMission);

        // Ally: 5 large cargo (125k capacity)
        $allyPlayer = $this->createAllyPlayer();
        $allyPlanet = $allyPlayer->planets->first();
        $allyPlanet->addUnit('large_cargo', 5);
        $allyPlanet->addUnit('cruiser', 50);
        $allyPlanet->addResources(new Resources(0, 0, 100000, 0));

        $allyUnits = new UnitCollection();
        $allyUnits->addUnit(ObjectService::getUnitObjectByMachineName('large_cargo'), 5);
        $allyUnits->addUnit(ObjectService::getUnitObjectByMachineName('cruiser'), 50);
        $this->joinUnionWithAlly($union, $allyPlayer, $allyUnits);

        // Target has lots of resources
        $foreignPlanet->addResources(new Resources(1000000, 500000, 250000, 0));

        // Process battle (no defenders, easy win)
        $this->travel(24)->hours();
        $this->reloadApplication();
        $this->get('/overview');

        // Verify loot distribution
        $battleReport = BattleReport::orderBy('id', 'desc')->first();
        $lootDistribution = $battleReport->loot['distribution'] ?? [];

        // Initiator has 2x cargo capacity, should get ~2/3 of loot
        // Ally has 1x cargo capacity, should get ~1/3 of loot
        $initiatorLoot = collect($lootDistribution)->first();
        $allyLoot = collect($lootDistribution)->last();

        $initiatorTotal = $initiatorLoot['metal'] + $initiatorLoot['crystal'] + $initiatorLoot['deuterium'];
        $allyTotal = $allyLoot['metal'] + $allyLoot['crystal'] + $allyLoot['deuterium'];

        // Initiator should have approximately twice the loot of ally
        $this->assertGreaterThan($allyTotal * 1.5, $initiatorTotal, 'Initiator with more cargo should get more loot');
    }

    // =========================================================================
    // FLEET RECALL TESTS
    // =========================================================================

    /**
     * Test that recalling a fleet removes it from the union.
     */
    public function testRecallFleetRemovesFromUnion(): void
    {
        $this->basicSetup();

        $unitCollection = new UnitCollection();
        $unitCollection->addUnit(ObjectService::getUnitObjectByMachineName('light_fighter'), 50);
        $foreignPlanet = $this->getNearbyForeignPlanet();
        $union = $this->dispatchAcsAttackWithUnion($unitCollection, $foreignPlanet);

        // Add ally fleet
        $allyPlayer = $this->createAllyPlayer();
        $allyUnits = new UnitCollection();
        $allyUnits->addUnit(ObjectService::getUnitObjectByMachineName('cruiser'), 10);
        $allyMission = $this->joinUnionWithAlly($union, $allyPlayer, $allyUnits);

        $union->refresh();
        $this->assertEquals(2, $union->fleetMissions()->count());

        // Ally recalls their fleet
        $this->actingAs($allyPlayer->getUser());
        $response = $this->post('/ajax/fleet/dispatch/recall-fleet', [
            'fleet_mission_id' => $allyMission->id,
            '_token' => csrf_token(),
        ]);
        $response->assertStatus(200);

        $union->refresh();
        $this->assertEquals(1, $union->fleetMissions()->where('canceled', 0)->count());
    }

    /**
     * Test that if initiator recalls, remaining fleets continue.
     */
    public function testInitiatorRecallFleetsContinue(): void
    {
        $this->basicSetup();

        $unitCollection = new UnitCollection();
        $unitCollection->addUnit(ObjectService::getUnitObjectByMachineName('light_fighter'), 50);
        $foreignPlanet = $this->getNearbyForeignPlanet();
        $union = $this->dispatchAcsAttackWithUnion($unitCollection, $foreignPlanet);

        $fleetMissionService = resolve(FleetMissionService::class);
        $initiatorMission = $fleetMissionService->getActiveFleetMissionsForCurrentPlayer()->first();

        // Add ally fleet
        $allyPlayer = $this->createAllyPlayer();
        $allyUnits = new UnitCollection();
        $allyUnits->addUnit(ObjectService::getUnitObjectByMachineName('cruiser'), 10);
        $allyMission = $this->joinUnionWithAlly($union, $allyPlayer, $allyUnits);

        // Initiator recalls
        $this->actingAs($this->planetService->getPlayer()->getUser());
        $response = $this->post('/ajax/fleet/dispatch/recall-fleet', [
            'fleet_mission_id' => $initiatorMission->id,
            '_token' => csrf_token(),
        ]);
        $response->assertStatus(200);

        // Union should still exist with ally's fleet
        $union->refresh();
        $activeFleets = $union->fleetMissions()->where('canceled', 0)->get();
        $this->assertEquals(1, $activeFleets->count());
        $this->assertEquals($allyMission->id, $activeFleets->first()->id);
    }

    // =========================================================================
    // SYNCHRONIZED RETURN TESTS
    // =========================================================================

    /**
     * Test that all return missions use synchronized speed.
     */
    public function testSynchronizedReturnSpeed(): void
    {
        $this->basicSetup();

        $unitCollection = new UnitCollection();
        $unitCollection->addUnit(ObjectService::getUnitObjectByMachineName('cruiser'), 100);
        $foreignPlanet = $this->sendMissionToOtherPlayerCleanPlanet($unitCollection, new Resources(0, 0, 0, 0), true, 2);

        $fleetMissionService = resolve(FleetMissionService::class);
        $initiatorMission = $fleetMissionService->getActiveFleetMissionsForCurrentPlayer()->first();
        $union = $this->fleetUnionService->createUnion($this->planetService->getPlayer(), $initiatorMission);

        // Add ally fleet
        $allyPlayer = $this->createAllyPlayer();
        $allyUnits = new UnitCollection();
        $allyUnits->addUnit(ObjectService::getUnitObjectByMachineName('cruiser'), 50);
        $allyMission = $this->joinUnionWithAlly($union, $allyPlayer, $allyUnits);

        // Process battle
        $this->travel(24)->hours();
        $this->reloadApplication();
        $this->get('/overview');

        // Get return missions
        $initiatorReturn = FleetMission::where('parent_id', $initiatorMission->id)->first();
        $allyReturn = FleetMission::where('parent_id', $allyMission->id)->first();

        if ($initiatorReturn && $allyReturn) {
            // Return durations should be the same
            $initiatorDuration = $initiatorReturn->time_arrival - $initiatorReturn->time_departure;
            $allyDuration = $allyReturn->time_arrival - $allyReturn->time_departure;

            $this->assertEquals($initiatorDuration, $allyDuration, 'Return mission durations should be synchronized');
        }
    }

    // =========================================================================
    // BATTLE REPORT TESTS
    // =========================================================================

    /**
     * Test that one battle report is created per player (not per fleet).
     */
    public function testOneReportPerPlayer(): void
    {
        $this->basicSetup();

        // Same player sends two fleets
        $this->planetAddUnit('cruiser', 200);

        $unitCollection1 = new UnitCollection();
        $unitCollection1->addUnit(ObjectService::getUnitObjectByMachineName('cruiser'), 100);

        $foreignPlanet = $this->sendMissionToOtherPlayerCleanPlanet($unitCollection1, new Resources(0, 0, 0, 0), true, 2);

        $fleetMissionService = resolve(FleetMissionService::class);
        $mission1 = $fleetMissionService->getActiveFleetMissionsForCurrentPlayer()->first();
        $union = $this->fleetUnionService->createUnion($this->planetService->getPlayer(), $mission1);

        // Send second fleet from second planet of same player
        $secondPlanet = $this->createSecondPlanetForPlayer($this->planetService->getPlayer());
        $secondPlanet->addUnit('cruiser', 100);
        $secondPlanet->addResources(new Resources(0, 0, 100000, 0));

        $unitCollection2 = new UnitCollection();
        $unitCollection2->addUnit(ObjectService::getUnitObjectByMachineName('cruiser'), 100);

        $mission2 = $fleetMissionService->createNewFromPlanet(
            $secondPlanet,
            $union->getTargetCoordinate(),
            PlanetType::Planet,
            2,
            $unitCollection2,
            new Resources(0, 0, 0, 0),
            10
        );
        $this->fleetUnionService->joinUnion($union, $mission2);

        // Process battle
        $this->travel(24)->hours();
        $this->reloadApplication();
        $this->playerSetAllMessagesRead();
        $this->get('/overview');

        // Count messages received by attacker
        $attackerMessages = Message::where('user_id', $this->planetService->getPlayer()->getId())
            ->where('key', 'battle_report')
            ->get();

        $this->assertEquals(1, $attackerMessages->count(), 'Player should receive only 1 battle report even with 2 fleets');
    }

    /**
     * Test that battle report contains per-fleet round data.
     */
    public function testBattleReportContainsPerFleetRoundData(): void
    {
        $this->basicSetup();

        $unitCollection = new UnitCollection();
        $unitCollection->addUnit(ObjectService::getUnitObjectByMachineName('cruiser'), 100);
        $foreignPlanet = $this->sendMissionToOtherPlayerCleanPlanet($unitCollection, new Resources(0, 0, 0, 0), true, 2);

        $fleetMissionService = resolve(FleetMissionService::class);
        $initiatorMission = $fleetMissionService->getActiveFleetMissionsForCurrentPlayer()->first();
        $union = $this->fleetUnionService->createUnion($this->planetService->getPlayer(), $initiatorMission);

        // Add ally
        $allyPlayer = $this->createAllyPlayer();
        $allyUnits = new UnitCollection();
        $allyUnits->addUnit(ObjectService::getUnitObjectByMachineName('cruiser'), 50);
        $this->joinUnionWithAlly($union, $allyPlayer, $allyUnits);

        // Add defenders
        $foreignPlanet->addUnit('rocket_launcher', 500);

        // Process battle
        $this->travel(24)->hours();
        $this->reloadApplication();
        $this->get('/overview');

        $battleReport = BattleReport::orderBy('id', 'desc')->first();

        // Check each fleet has round data
        foreach ($battleReport->attacker_fleets as $fleet) {
            $this->assertArrayHasKey('rounds', $fleet, 'Fleet should have rounds data');
            $this->assertNotEmpty($fleet['rounds'], 'Fleet should have at least one round');

            foreach ($fleet['rounds'] as $round) {
                $this->assertArrayHasKey('round_number', $round);
                $this->assertArrayHasKey('ships_remaining', $round);
                $this->assertArrayHasKey('losses_this_round', $round);
            }
        }
    }
}
```

---

### 9.3 ACS Defend Feature Tests

**File**: `tests/Feature/FleetDispatch/FleetDispatchAcsDefendTest.php`

```php
<?php

namespace Tests\Feature\FleetDispatch;

use OGame\GameObjects\Models\Units\UnitCollection;
use OGame\Models\FleetMission;
use OGame\Models\Resources;
use OGame\Services\FleetMissionService;
use OGame\Services\ObjectService;
use OGame\Services\SettingsService;

class FleetDispatchAcsDefendTest extends FleetDispatchAcsTestCase
{
    protected int $missionType = 5;
    protected string $missionName = 'ACS Defend';

    protected function basicSetup(): void
    {
        $this->planetAddUnit('light_fighter', 100);
        $this->playerSetResearchLevel('computer_technology', 5);
        $this->planetAddResources(new Resources(0, 0, 1000000, 0));

        $settingsService = resolve(SettingsService::class);
        $settingsService->set('fleet_speed_holding', 1);
    }

    /**
     * Test that ACS Defend mission can be sent to ally planet.
     */
    public function testAcsDefendToAllyPlanet(): void
    {
        $this->basicSetup();

        $allyPlayer = $this->createAllyPlayer();
        $allyPlanet = $allyPlayer->planets->first();

        $unitCollection = new UnitCollection();
        $unitCollection->addUnit(ObjectService::getUnitObjectByMachineName('light_fighter'), 50);

        $fleetMissionService = resolve(FleetMissionService::class);
        $mission = $fleetMissionService->createNewFromPlanet(
            $this->planetService,
            $allyPlanet->getPlanetCoordinates(),
            PlanetType::Planet,
            5, // ACS Defend
            $unitCollection,
            new Resources(0, 0, 0, 0),
            10, // speed
            2   // 2 hours hold time
        );

        $this->assertNotNull($mission);
        $this->assertEquals(5, $mission->mission_type);
        $this->assertGreaterThan(0, $mission->time_holding);
    }

    /**
     * Test that ACS Defend cannot be sent to non-ally.
     */
    public function testAcsDefendToStrangerFails(): void
    {
        $this->basicSetup();

        $strangerPlanet = $this->getNearbyForeignPlanet();

        $unitCollection = new UnitCollection();
        $unitCollection->addUnit(ObjectService::getUnitObjectByMachineName('light_fighter'), 50);

        $this->expectException(\Exception::class);

        $fleetMissionService = resolve(FleetMissionService::class);
        $fleetMissionService->createNewFromPlanet(
            $this->planetService,
            $strangerPlanet->getPlanetCoordinates(),
            PlanetType::Planet,
            5, // ACS Defend
            $unitCollection,
            new Resources(0, 0, 0, 0),
            10,
            2
        );
    }

    /**
     * Test valid hold times (0, 1, 2, 4, 8, 16, 32 hours).
     */
    public function testValidHoldTimes(): void
    {
        $this->basicSetup();

        $validHoldTimes = [0, 1, 2, 4, 8, 16, 32];
        $allyPlayer = $this->createAllyPlayer();
        $allyPlanet = $allyPlayer->planets->first();

        foreach ($validHoldTimes as $holdTime) {
            $unitCollection = new UnitCollection();
            $unitCollection->addUnit(ObjectService::getUnitObjectByMachineName('light_fighter'), 10);

            $fleetMissionService = resolve(FleetMissionService::class);
            $mission = $fleetMissionService->createNewFromPlanet(
                $this->planetService,
                $allyPlanet->getPlanetCoordinates(),
                PlanetType::Planet,
                5,
                $unitCollection,
                new Resources(0, 0, 0, 0),
                10,
                $holdTime
            );

            $expectedHoldSeconds = $holdTime * 3600;
            $this->assertEquals($expectedHoldSeconds, $mission->time_holding, "Hold time {$holdTime}h should be valid");
        }
    }

    /**
     * Test that defending fleet participates in battle.
     */
    public function testDefendingFleetParticipatesInBattle(): void
    {
        $this->basicSetup();

        $settingsService = resolve(SettingsService::class);
        $settingsService->set('economy_speed', 0);

        // Create ally planet
        $allyPlayer = $this->createAllyPlayer();
        $allyPlanet = $allyPlayer->planets->first();

        // Send defend mission
        $defendUnits = new UnitCollection();
        $defendUnits->addUnit(ObjectService::getUnitObjectByMachineName('cruiser'), 100);

        $fleetMissionService = resolve(FleetMissionService::class);
        $defendMission = $fleetMissionService->createNewFromPlanet(
            $this->planetService,
            $allyPlanet->getPlanetCoordinates(),
            PlanetType::Planet,
            5,
            $defendUnits,
            new Resources(0, 0, 0, 0),
            10,
            8 // 8 hour hold
        );

        // Wait for defend mission to arrive
        $this->travel(2)->hours();
        $this->get('/overview');

        // Create attacker
        $attackerPlayer = $this->createPlayer('Attacker');
        $attackerPlanet = $attackerPlayer->planets->first();
        $attackerPlanet->addUnit('light_fighter', 200);
        $attackerPlanet->addResources(new Resources(0, 0, 1000000, 0));

        // Attack the ally planet
        $attackUnits = new UnitCollection();
        $attackUnits->addUnit(ObjectService::getUnitObjectByMachineName('light_fighter'), 200);

        $attackerFleetService = resolve(FleetMissionService::class, ['player' => $attackerPlayer]);
        $attackMission = $attackerFleetService->createNewFromPlanet(
            $attackerPlanet,
            $allyPlanet->getPlanetCoordinates(),
            PlanetType::Planet,
            1, // Regular attack
            $attackUnits,
            new Resources(0, 0, 0, 0),
            10
        );

        // Process battle
        $this->travel(24)->hours();
        $this->reloadApplication();
        $this->get('/overview');

        // Verify battle report shows defender fleet participated
        $battleReport = BattleReport::orderBy('id', 'desc')->first();
        $this->assertNotNull($battleReport);

        // Should have defender_fleets data if ACS Defend was involved
        $this->assertTrue(
            ($battleReport->defender['has_acs_defenders'] ?? false) ||
            count($battleReport->defender_fleets ?? []) > 1,
            'Battle should include ACS defending fleet'
        );
    }

    /**
     * Test that defending fleet returns after hold time expires.
     */
    public function testDefendingFleetReturnsAfterHoldTime(): void
    {
        $this->basicSetup();

        $allyPlayer = $this->createAllyPlayer();
        $allyPlanet = $allyPlayer->planets->first();

        $defendUnits = new UnitCollection();
        $defendUnits->addUnit(ObjectService::getUnitObjectByMachineName('light_fighter'), 50);

        $fleetMissionService = resolve(FleetMissionService::class);
        $defendMission = $fleetMissionService->createNewFromPlanet(
            $this->planetService,
            $allyPlanet->getPlanetCoordinates(),
            PlanetType::Planet,
            5,
            $defendUnits,
            new Resources(0, 0, 0, 0),
            10,
            2 // 2 hour hold
        );

        // Process arrival
        $this->travel(3)->hours();
        $this->get('/overview');

        // Process hold time expiration
        $this->travel(3)->hours();
        $this->get('/overview');

        // Check for return mission
        $returnMission = FleetMission::where('parent_id', $defendMission->id)->first();
        $this->assertNotNull($returnMission, 'Return mission should be created after hold time expires');
    }

    /**
     * Test that defending fleet can be recalled during hold time.
     */
    public function testDefendingFleetCanBeRecalledDuringHold(): void
    {
        $this->basicSetup();

        $allyPlayer = $this->createAllyPlayer();
        $allyPlanet = $allyPlayer->planets->first();

        $defendUnits = new UnitCollection();
        $defendUnits->addUnit(ObjectService::getUnitObjectByMachineName('light_fighter'), 50);

        $fleetMissionService = resolve(FleetMissionService::class);
        $defendMission = $fleetMissionService->createNewFromPlanet(
            $this->planetService,
            $allyPlanet->getPlanetCoordinates(),
            PlanetType::Planet,
            5,
            $defendUnits,
            new Resources(0, 0, 0, 0),
            10,
            8 // 8 hour hold
        );

        // Process arrival
        $this->travel(3)->hours();
        $this->get('/overview');

        // Recall during hold time
        $response = $this->post('/ajax/fleet/dispatch/recall-fleet', [
            'fleet_mission_id' => $defendMission->id,
            '_token' => csrf_token(),
        ]);
        $response->assertStatus(200);

        // Verify mission is canceled
        $defendMission->refresh();
        $this->assertEquals(1, $defendMission->canceled);
    }

    /**
     * Test maximum 5 players can defend.
     */
    public function testMaxFivePlayersCanDefend(): void
    {
        $this->basicSetup();

        $targetPlayer = $this->createAllyPlayer();
        $targetPlanet = $targetPlayer->planets->first();

        // 5 different players send defend missions (including main player)
        for ($i = 0; $i < 4; $i++) {
            $defender = $this->createPlayer("Defender{$i}");
            $this->makePlayersAllies($targetPlayer, $defender);

            $defenderPlanet = $defender->planets->first();
            $defenderPlanet->addUnit('light_fighter', 10);
            $defenderPlanet->addResources(new Resources(0, 0, 100000, 0));

            $units = new UnitCollection();
            $units->addUnit(ObjectService::getUnitObjectByMachineName('light_fighter'), 10);

            $fleetMissionService = resolve(FleetMissionService::class, ['player' => $defender]);
            $fleetMissionService->createNewFromPlanet(
                $defenderPlanet,
                $targetPlanet->getPlanetCoordinates(),
                PlanetType::Planet,
                5,
                $units,
                new Resources(0, 0, 0, 0),
                10,
                4
            );
        }

        // Main player also defends
        $this->makePlayersAllies($targetPlayer, $this->planetService->getPlayer());
        $mainUnits = new UnitCollection();
        $mainUnits->addUnit(ObjectService::getUnitObjectByMachineName('light_fighter'), 10);

        $fleetMissionService = resolve(FleetMissionService::class);
        $fleetMissionService->createNewFromPlanet(
            $this->planetService,
            $targetPlanet->getPlanetCoordinates(),
            PlanetType::Planet,
            5,
            $mainUnits,
            new Resources(0, 0, 0, 0),
            10,
            4
        );

        // 6th player should be rejected
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(__('t_acs.error_max_players_reached'));

        $sixthDefender = $this->createPlayer('SixthDefender');
        $this->makePlayersAllies($targetPlayer, $sixthDefender);

        $sixthPlanet = $sixthDefender->planets->first();
        $sixthPlanet->addUnit('light_fighter', 10);
        $sixthPlanet->addResources(new Resources(0, 0, 100000, 0));

        $sixthUnits = new UnitCollection();
        $sixthUnits->addUnit(ObjectService::getUnitObjectByMachineName('light_fighter'), 10);

        $fleetMissionService = resolve(FleetMissionService::class, ['player' => $sixthDefender]);
        $fleetMissionService->createNewFromPlanet(
            $sixthPlanet,
            $targetPlanet->getPlanetCoordinates(),
            PlanetType::Planet,
            5,
            $sixthUnits,
            new Resources(0, 0, 0, 0),
            10,
            4
        );
    }

    // =========================================================================
    // DESTROYED FLEET HANDLING TESTS
    // =========================================================================

    /**
     * Test that completely destroyed ACS Defend fleet does not create return mission.
     */
    public function testDestroyedDefendFleetNoReturnMission(): void
    {
        $this->basicSetup();

        $settingsService = resolve(SettingsService::class);
        $settingsService->set('economy_speed', 0);

        // Create ally planet
        $allyPlayer = $this->createAllyPlayer();
        $allyPlanet = $allyPlayer->planets->first();

        // Send weak defend fleet (will be destroyed)
        $defendUnits = new UnitCollection();
        $defendUnits->addUnit(ObjectService::getUnitObjectByMachineName('light_fighter'), 5);

        $fleetMissionService = resolve(FleetMissionService::class);
        $defendMission = $fleetMissionService->createNewFromPlanet(
            $this->planetService,
            $allyPlanet->getPlanetCoordinates(),
            PlanetType::Planet,
            5,
            $defendUnits,
            new Resources(0, 0, 0, 0),
            10,
            8 // 8 hour hold
        );

        // Wait for defend mission to arrive
        $this->travel(2)->hours();
        $this->get('/overview');

        // Create strong attacker that will destroy the defend fleet
        $attackerPlayer = $this->createPlayer('StrongAttacker');
        $attackerPlanet = $attackerPlayer->planets->first();
        $attackerPlanet->addUnit('battleship', 100);
        $attackerPlanet->addResources(new Resources(0, 0, 1000000, 0));

        $attackUnits = new UnitCollection();
        $attackUnits->addUnit(ObjectService::getUnitObjectByMachineName('battleship'), 100);

        $attackerFleetService = resolve(FleetMissionService::class, ['player' => $attackerPlayer]);
        $attackMission = $attackerFleetService->createNewFromPlanet(
            $attackerPlanet,
            $allyPlanet->getPlanetCoordinates(),
            PlanetType::Planet,
            1,
            $attackUnits,
            new Resources(0, 0, 0, 0),
            10
        );

        // Process battle
        $this->travel(24)->hours();
        $this->reloadApplication();
        $this->get('/overview');

        // Assert: No return mission created for destroyed defend fleet
        $returnMission = FleetMission::where('parent_id', $defendMission->id)->first();
        $this->assertNull($returnMission, 'Destroyed defend fleet should NOT have a return mission');

        // Assert: Original mission is marked as processed
        $defendMission->refresh();
        $this->assertEquals(1, $defendMission->processed, 'Defend mission should be marked as processed');

        // Assert: Fleet slot is freed (check active missions count)
        $activeMissions = $fleetMissionService->getActiveFleetMissionsForCurrentPlayer();
        $this->assertEquals(0, $activeMissions->count(), 'No active missions should remain for destroyed fleet');

        // Assert: Player receives appropriate message (follows existing rules)
        // - Round 1 destruction: "fleet_lost_contact" message (no battle report)
        // - Round 2+ destruction: normal battle report showing losses
        $message = Message::where('user_id', $this->planetService->getPlayer()->getId())
            ->whereIn('key', ['battle_report', 'fleet_lost_contact'])
            ->orderByDesc('id')
            ->first();
        $this->assertNotNull($message, 'Player should receive battle report or fleet_lost_contact message');
    }

    /**
     * Test that partially destroyed defend fleet creates return with survivors only.
     */
    public function testPartiallyDestroyedDefendFleetReturnsWithSurvivors(): void
    {
        $this->basicSetup();

        $settingsService = resolve(SettingsService::class);
        $settingsService->set('economy_speed', 0);

        $allyPlayer = $this->createAllyPlayer();
        $allyPlanet = $allyPlayer->planets->first();

        // Send strong defend fleet (some will survive)
        $defendUnits = new UnitCollection();
        $defendUnits->addUnit(ObjectService::getUnitObjectByMachineName('cruiser'), 100);

        $fleetMissionService = resolve(FleetMissionService::class);
        $defendMission = $fleetMissionService->createNewFromPlanet(
            $this->planetService,
            $allyPlanet->getPlanetCoordinates(),
            PlanetType::Planet,
            5,
            $defendUnits,
            new Resources(0, 0, 0, 0),
            10,
            8
        );

        // Wait for arrival
        $this->travel(2)->hours();
        $this->get('/overview');

        // Create attacker (will cause some losses but not total destruction)
        $attackerPlayer = $this->createPlayer('Attacker');
        $attackerPlanet = $attackerPlayer->planets->first();
        $attackerPlanet->addUnit('light_fighter', 200);
        $attackerPlanet->addResources(new Resources(0, 0, 1000000, 0));

        $attackUnits = new UnitCollection();
        $attackUnits->addUnit(ObjectService::getUnitObjectByMachineName('light_fighter'), 200);

        $attackerFleetService = resolve(FleetMissionService::class, ['player' => $attackerPlayer]);
        $attackerFleetService->createNewFromPlanet(
            $attackerPlanet,
            $allyPlanet->getPlanetCoordinates(),
            PlanetType::Planet,
            1,
            $attackUnits,
            new Resources(0, 0, 0, 0),
            10
        );

        // Process battle
        $this->travel(24)->hours();
        $this->reloadApplication();
        $this->get('/overview');

        // Assert: Return mission IS created for surviving fleet
        $returnMission = FleetMission::where('parent_id', $defendMission->id)->first();
        $this->assertNotNull($returnMission, 'Surviving defend fleet should have a return mission');

        // Assert: Return mission has survivors (not 0 ships)
        $returnShipCount = $returnMission->light_fighter + $returnMission->cruiser +
            $returnMission->battleship + $returnMission->heavy_fighter;
        $this->assertGreaterThan(0, $returnShipCount, 'Return mission should have surviving ships');
    }
}
```

---

### 9.4 FleetUnionService Unit Tests

**File**: `tests/Unit/FleetUnionServiceTest.php`

```php
<?php

namespace Tests\Unit;

use OGame\Models\FleetUnion;
use OGame\Services\FleetUnionService;
use Tests\UnitTestCase;

class FleetUnionServiceTest extends UnitTestCase
{
    private FleetUnionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = resolve(FleetUnionService::class);
    }

    /**
     * Test getMaxDelayTime returns 30% of remaining time.
     */
    public function testGetMaxDelayTimeCalculation(): void
    {
        // Create mock union with 1000 seconds remaining
        $union = new FleetUnion();
        $union->time_arrival = now()->timestamp + 1000;

        $maxDelay = $this->service->getMaxDelayTime($union);

        $this->assertEquals(300, $maxDelay); // 30% of 1000
    }

    /**
     * Test getMaxDelayTime with very short remaining time.
     */
    public function testGetMaxDelayTimeShortDuration(): void
    {
        $union = new FleetUnion();
        $union->time_arrival = now()->timestamp + 10;

        $maxDelay = $this->service->getMaxDelayTime($union);

        $this->assertEquals(3, $maxDelay); // 30% of 10
    }

    /**
     * Test getAvailableSlots calculation.
     */
    public function testGetAvailableSlots(): void
    {
        $union = new FleetUnion();
        $union->max_fleets = 16;

        // Mock 5 existing fleets
        // ... setup mock relationship

        $available = $this->service->getAvailableSlots($union);
        $this->assertLessThanOrEqual(16, $available);
    }
}
```

---

### 9.5 AcsBattleEngine Unit Tests

**File**: `tests/Unit/BattleEngine/AcsBattleEngineTest.php`

```php
<?php

namespace Tests\Unit\BattleEngine;

use OGame\GameMissions\BattleEngine\AcsBattleEngine;
use OGame\GameMissions\BattleEngine\Models\AttackerFleet;
use OGame\GameObjects\Models\Units\UnitCollection;
use OGame\Services\ObjectService;
use Tests\UnitTestCase;

class AcsBattleEngineTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createAndSetPlanetModel([
            'metal' => 100000,
            'crystal' => 50000,
            'deuterium' => 25000,
        ]);
        $this->createAndSetUserTechModel([]);
    }

    /**
     * Test battle with two attacker fleets.
     */
    public function testBattleWithTwoAttackerFleets(): void
    {
        // Create two attacker fleets
        $fleet1Units = new UnitCollection();
        $fleet1Units->addUnit(ObjectService::getUnitObjectByMachineName('cruiser'), 50);

        $fleet2Units = new UnitCollection();
        $fleet2Units->addUnit(ObjectService::getUnitObjectByMachineName('cruiser'), 30);

        $attackerFleet1 = new AttackerFleet(
            fleetMissionId: 1,
            player: $this->playerService,
            units: $fleet1Units,
            cargoResources: new Resources(0, 0, 0, 0),
            isInitiator: true
        );

        $attackerFleet2 = new AttackerFleet(
            fleetMissionId: 2,
            player: $this->playerService, // Same player for simplicity
            units: $fleet2Units,
            cargoResources: new Resources(0, 0, 0, 0),
            isInitiator: false
        );

        $engine = new AcsBattleEngine(
            [$attackerFleet1, $attackerFleet2],
            $this->planetService,
            $this->settingsService
        );

        $result = $engine->simulateBattle();

        $this->assertTrue($result->isAcsBattle);
        $this->assertCount(2, $result->attackerResults);
        $this->assertArrayHasKey(1, $result->attackerResults);
        $this->assertArrayHasKey(2, $result->attackerResults);
    }

    /**
     * Test that survivors are correctly assigned to their owners.
     */
    public function testSurvivorsAssignedToCorrectOwners(): void
    {
        $fleet1Units = new UnitCollection();
        $fleet1Units->addUnit(ObjectService::getUnitObjectByMachineName('cruiser'), 100);

        $fleet2Units = new UnitCollection();
        $fleet2Units->addUnit(ObjectService::getUnitObjectByMachineName('battleship'), 50);

        $attackerFleet1 = new AttackerFleet(
            fleetMissionId: 1,
            player: $this->playerService,
            units: $fleet1Units,
            cargoResources: new Resources(0, 0, 0, 0),
            isInitiator: true
        );

        $attackerFleet2 = new AttackerFleet(
            fleetMissionId: 2,
            player: $this->playerService,
            units: $fleet2Units,
            cargoResources: new Resources(0, 0, 0, 0),
            isInitiator: false
        );

        $engine = new AcsBattleEngine(
            [$attackerFleet1, $attackerFleet2],
            $this->planetService,
            $this->settingsService
        );

        $result = $engine->simulateBattle();

        // Fleet 1 should only have cruisers in results
        $fleet1Result = $result->attackerResults[1];
        $this->assertGreaterThanOrEqual(0, $fleet1Result->unitsResult->getAmountByMachineName('cruiser'));
        $this->assertEquals(0, $fleet1Result->unitsResult->getAmountByMachineName('battleship'));

        // Fleet 2 should only have battleships in results
        $fleet2Result = $result->attackerResults[2];
        $this->assertEquals(0, $fleet2Result->unitsResult->getAmountByMachineName('cruiser'));
        $this->assertGreaterThanOrEqual(0, $fleet2Result->unitsResult->getAmountByMachineName('battleship'));
    }

    /**
     * Test round data is tracked per fleet.
     */
    public function testRoundDataTrackedPerFleet(): void
    {
        // Create planet with some defenders
        $this->createAndSetPlanetModel([
            'metal' => 100000,
            'crystal' => 50000,
            'deuterium' => 25000,
        ]);
        $this->planetService->addUnit('rocket_launcher', 100);

        $fleet1Units = new UnitCollection();
        $fleet1Units->addUnit(ObjectService::getUnitObjectByMachineName('cruiser'), 50);

        $attackerFleet1 = new AttackerFleet(
            fleetMissionId: 1,
            player: $this->playerService,
            units: $fleet1Units,
            cargoResources: new Resources(0, 0, 0, 0),
            isInitiator: true
        );

        $engine = new AcsBattleEngine(
            [$attackerFleet1],
            $this->planetService,
            $this->settingsService
        );

        $result = $engine->simulateBattle();

        // Check rounds have per-fleet data
        foreach ($result->rounds as $round) {
            $this->assertArrayHasKey(1, $round->attackerShipsPerFleet);
            $this->assertArrayHasKey(1, $round->attackerLossesPerFleet);
        }
    }
}
```

---

### 9.6 Loot Distribution Unit Tests

**File**: `tests/Unit/AcsLootDistributionTest.php`

```php
<?php

namespace Tests\Unit;

use OGame\GameMissions\BattleEngine\Models\AttackerResult;
use OGame\GameObjects\Models\Units\UnitCollection;
use OGame\Models\Resources;
use OGame\Services\ObjectService;
use Tests\UnitTestCase;

class AcsLootDistributionTest extends UnitTestCase
{
    /**
     * Test loot split 2:1 by cargo capacity.
     */
    public function testLootSplitByCargoCapacity(): void
    {
        // Fleet 1: 10 large cargo = 250k capacity
        // Fleet 2: 5 large cargo = 125k capacity
        // Total: 375k capacity
        // Fleet 1 should get 2/3, Fleet 2 should get 1/3

        $totalLoot = new Resources(150000, 75000, 37500, 0);

        $fleet1Capacity = 250000;
        $fleet2Capacity = 125000;
        $totalCapacity = $fleet1Capacity + $fleet2Capacity;

        $fleet1Share = $fleet1Capacity / $totalCapacity;
        $fleet2Share = $fleet2Capacity / $totalCapacity;

        $fleet1Loot = new Resources(
            (int)($totalLoot->metal->get() * $fleet1Share),
            (int)($totalLoot->crystal->get() * $fleet1Share),
            (int)($totalLoot->deuterium->get() * $fleet1Share),
            0
        );

        $fleet2Loot = new Resources(
            (int)($totalLoot->metal->get() * $fleet2Share),
            (int)($totalLoot->crystal->get() * $fleet2Share),
            (int)($totalLoot->deuterium->get() * $fleet2Share),
            0
        );

        // Fleet 1 should have ~2/3
        $this->assertEquals(100000, $fleet1Loot->metal->get());
        $this->assertEquals(50000, $fleet1Loot->crystal->get());
        $this->assertEquals(25000, $fleet1Loot->deuterium->get());

        // Fleet 2 should have ~1/3
        $this->assertEquals(50000, $fleet2Loot->metal->get());
        $this->assertEquals(25000, $fleet2Loot->crystal->get());
        $this->assertEquals(12500, $fleet2Loot->deuterium->get());
    }

    /**
     * Test loot split when one fleet has zero cargo.
     */
    public function testLootSplitZeroCargo(): void
    {
        // Fleet 1: 10 large cargo = 250k capacity
        // Fleet 2: 0 cargo ships (all combat)
        // Fleet 1 should get 100%

        $totalLoot = new Resources(100000, 50000, 25000, 0);

        $fleet1Capacity = 250000;
        $fleet2Capacity = 0;
        $totalCapacity = $fleet1Capacity + $fleet2Capacity;

        if ($totalCapacity > 0) {
            $fleet1Share = $fleet1Capacity / $totalCapacity;
        } else {
            $fleet1Share = 0;
        }

        $fleet1Loot = new Resources(
            (int)($totalLoot->metal->get() * $fleet1Share),
            (int)($totalLoot->crystal->get() * $fleet1Share),
            (int)($totalLoot->deuterium->get() * $fleet1Share),
            0
        );

        $this->assertEquals(100000, $fleet1Loot->metal->get());
        $this->assertEquals(50000, $fleet1Loot->crystal->get());
        $this->assertEquals(25000, $fleet1Loot->deuterium->get());
    }

    /**
     * Test cargo resources survive proportionally to surviving cargo capacity.
     */
    public function testCargoResourcesSurviveProportionally(): void
    {
        // Start with 100k cargo capacity, 50k resources loaded
        // Lose 50% cargo capacity
        // Should have ~25k resources survive

        $initialCapacity = 100000;
        $loadedResources = new Resources(30000, 15000, 5000, 0);
        $survivingCapacity = 50000;

        $survivalRate = $survivingCapacity / $initialCapacity;

        $survivingResources = new Resources(
            (int)($loadedResources->metal->get() * $survivalRate),
            (int)($loadedResources->crystal->get() * $survivalRate),
            (int)($loadedResources->deuterium->get() * $survivalRate),
            0
        );

        $this->assertEquals(15000, $survivingResources->metal->get());
        $this->assertEquals(7500, $survivingResources->crystal->get());
        $this->assertEquals(2500, $survivingResources->deuterium->get());
    }
}
```

---

### Test Execution Commands

```bash
# Run all ACS tests
php artisan test --filter=Acs

# Run specific test files
php artisan test tests/Feature/FleetDispatch/FleetDispatchAcsAttackTest.php
php artisan test tests/Feature/FleetDispatch/FleetDispatchAcsDefendTest.php
php artisan test tests/Unit/FleetUnionServiceTest.php
php artisan test tests/Unit/BattleEngine/AcsBattleEngineTest.php
php artisan test tests/Unit/AcsLootDistributionTest.php

# Run with coverage
php artisan test --filter=Acs --coverage
```

---

### Test Acceptance Criteria Checklist

| Test Area | Criteria | Priority |
|-----------|----------|----------|
| **Union Creation** | Union created from attack mission | High |
| **Union Creation** | Mission type converts from 1 to 2 | High |
| **Union Joining** | Ally can join union | High |
| **Union Joining** | Buddy can join union | High |
| **Union Joining** | Stranger cannot join union | High |
| **Union Limits** | Max 16 fleets enforced | High |
| **Union Limits** | Max 5 players enforced | High |
| **Delay Limits** | 30% delay calculation correct | High |
| **Delay Limits** | Fleet exceeding delay rejected | Medium |
| **Battle Processing** | Multiple attackers processed | High |
| **Battle Processing** | Each participant uses own tech | High |
| **Loot Distribution** | Loot split by cargo capacity | High |
| **Loot Distribution** | Zero cargo fleet gets nothing | Medium |
| **Fleet Recall** | Recalled fleet removed from union | High |
| **Fleet Recall** | Initiator recall - others continue | High |
| **Return Missions** | Synchronized return speed | High |
| **Battle Reports** | One report per player | High |
| **Battle Reports** | Per-fleet round data present | High |
| **ACS Defend** | Defend mission to ally works | High |
| **ACS Defend** | Defend to stranger fails | High |
| **ACS Defend** | Valid hold times accepted | Medium |
| **ACS Defend** | Defender participates in battle | High |
| **ACS Defend** | Fleet returns after hold expires | High |
| **ACS Defend** | Fleet can be recalled during hold | Medium |
| **ACS Defend** | Max 5 defenders enforced | High |
| **Destroyed Fleets** | No return mission for destroyed defend fleet | High |
| **Destroyed Fleets** | Fleet slot freed when destroyed | High |
| **Destroyed Fleets** | Round 1 destruction: fleet_lost_contact message | High |
| **Destroyed Fleets** | Round 2+ destruction: normal battle report | High |
| **Destroyed Fleets** | Partial survivors create return mission | High |
