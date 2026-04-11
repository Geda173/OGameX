# Alliance Combat System (ACS) Implementation Plan

## Overview

This document tracks the remaining work for the Alliance Combat System (ACS) in OGameX. ACS allows multiple players to coordinate fleets to attack or defend together.

---

## Completed Features

### ACS Defend ✅
- Basic hold mission (mission type 5)
- Multi-defender battle engine with per-fleet tracking
- Alliance Depot with supply rockets
- BattleUnit owner tracking (`fleetMissionId`, `ownerId`)

### ACS Attack ✅
- Fleet Unions foundation
- Multi-attacker battle engine (PHP + Rust)
- Union creation, invite system, coordinated battles
- Fleet events widget with union grouping
- Server setting enforcement (`alliance_combat_system_on`)

---

## Remaining Work

### PR 8a: Per-Fleet Loot Distribution
**Size**: ~100-150 lines
**System**: AttackMission, BattleEngine

**Code TODOs**:
```
app/GameMissions/AttackMission.php:230
app/GameMissions/BattleEngine/BattleEngine.php:68
```

**Scope**: When multiple attackers participate in ACS battle, distribute loot proportionally to each fleet's surviving cargo capacity.

**Acceptance Criteria**:
- [ ] Each fleet's loot share = (fleet cargo / total cargo) × total loot
- [ ] Fleet with no cargo or no survivors gets no loot
- [ ] Single-attacker battles unchanged (backward compatible)

---

### PR 8b: Reaper Debris in Multi-Attacker
**Size**: ~50-100 lines
**System**: AttackMission

**Code TODO**:
```
app/GameMissions/AttackMission.php:234
```

**Scope**: Reaper auto-collection should work in multi-attacker ACS battles. Any player with surviving Reapers collects debris (not just Generals - Generals can *build* Reapers, but anyone who has them can use them).

**Acceptance Criteria**:
- [ ] Each attacker with surviving Reapers collects their share of debris
- [ ] Debris split proportionally to Reaper cargo capacity across fleets
- [ ] Attackers without Reapers unaffected
- [ ] Debris collection shown in return mission

---

### PR 8c: Phalanx ACS Attack Display
**Size**: ~30-50 lines
**System**: PhalanxService

**Code TODO**:
```
app/Services/PhalanxService.php:319
```

**Scope**: Show ACS Attack (mission type 2) fleets in Phalanx scan results.

**Acceptance Criteria**:
- [ ] ACS Attack fleets visible in Phalanx scan
- [ ] Shows correct fleet composition and arrival time
- [ ] Union members shown individually or grouped (match OGame behavior)

---

### PR 8d: ACS Defend in Espionage
**Size**: ~100-150 lines
**System**: EspionageMission

**Code TODOs**:
```
app/GameMissions/EspionageMission.php:100
app/GameMissions/EspionageMission.php:465
```

**Scope**: Include ACS Defend fleets in espionage calculations and reports.

**Acceptance Criteria**:
- [ ] ACS Defend fleets contribute to counter-espionage chance
- [ ] ACS Defend fleets appear in espionage report (ships section)
- [ ] Each defending fleet shown with owner info

---

### PR 9a: Battle Report to All Participants
**Size**: ~50-100 lines
**System**: AttackMission

**Code TODO**:
```
app/GameMissions/AttackMission.php:412
```

**Scope**: Send battle report to all participating ACS attackers, not just the initiator.

**Acceptance Criteria**:
- [ ] All union members receive the battle report
- [ ] Each player receives exactly one report (even with multiple fleets)
- [ ] Defender still receives report as before

---

### PR 9b: Per-Fleet Battle Report Display
**Size**: ~200-300 lines
**System**: AttackMission, BattleReport

**Code TODOs**:
```
app/GameMissions/AttackMission.php:672
app/GameMessages/BattleReport.php:143
```

**Scope**: Show per-fleet breakdown in battle reports for multi-attacker/defender battles.

**Acceptance Criteria**:
- [ ] Report shows each attacker fleet separately with tech levels
- [ ] Report shows defender + ACS Defend fleets separately
- [ ] Per-fleet losses and survivors displayed
- [ ] Single-attacker reports unchanged

---

### PR 10: Fleet Background Processing System
**Size**: ~400-500 lines
**System**: FleetController, FleetMissionService, Jobs, Migration

**Problems to solve:**
1. Current system relies on polling/user logins - not real-time
2. Multiple fleets at same second have non-deterministic order
3. No concurrency - all missions processed sequentially even when they don't conflict

**Solution: Laravel Jobs + Millisecond Precision**

Combines two complementary approaches:
- **Millisecond column** → determines *ordering* (who processes first)
- **Laravel delayed jobs** → determines *timing* (processes at exact arrival time)

#### Part 1: Millisecond Arrival Precision
```php
// Migration
$table->bigInteger('time_arrival_ms')->default(0);

// At dispatch (FleetController)
$arrivalTimeMs = (int)(($departureTime + $flightDuration) * 1000);
$mission->time_arrival_ms = $arrivalTimeMs;
```

#### Part 2: Background Job Processing
```php
// At dispatch - schedule job for exact arrival time
ProcessFleetArrival::dispatch($mission->id)
    ->delay(Carbon::createFromTimestamp($mission->time_arrival));

// Job processes in arrival order when multiple pending
class ProcessFleetArrival implements ShouldQueue
{
    public function handle(): void
    {
        // Lock on target planet to prevent conflicts
        Cache::lock("planet:{$targetPlanetId}", 30)->block(10, function () {
            // Process all arrived missions for this planet in ms order
            $missions = FleetMission::where('planet_id_to', $targetPlanetId)
                ->where('time_arrival', '<=', now()->timestamp)
                ->where('processed', 0)
                ->orderBy('time_arrival')
                ->orderBy('time_arrival_ms')
                ->get();
            
            foreach ($missions as $mission) {
                $this->processMission($mission);
            }
        });
    }
}
```

#### Concurrency Model
- Missions to **different planets** → process in parallel (no conflict)
- Missions to **same planet** → serialize with lock, order by `time_arrival_ms`
- Lock prevents race conditions when ninja + attack arrive simultaneously

#### Edge Cases
- **Fleet recall**: Delete pending job from queue
- **Server downtime**: Scheduler fallback catches missed jobs on restart
- **Processing delays**: Lock timeout prevents deadlocks

**Acceptance Criteria**:
- [ ] `time_arrival_ms` column stores millisecond-precision arrival time
- [ ] Jobs dispatched at fleet send, fire at arrival time
- [ ] Same-planet missions serialized, ordered by ms precision
- [ ] Different-planet missions can process concurrently
- [ ] Job cancelled on fleet recall
- [ ] Scheduler fallback processes backlog after downtime
- [ ] Processing within 1-3 seconds of scheduled time

---

## Summary

| PR | Focus | Size | Dependencies |
|----|-------|------|--------------|
| 8a | Loot distribution | ~100 lines | None |
| 8b | Reaper debris | ~50 lines | None |
| 8c | Phalanx display | ~30 lines | None |
| 8d | Espionage integration | ~100 lines | None |
| 9a | Reports to all players | ~50 lines | None |
| 9b | Per-fleet report display | ~200 lines | 9a (optional) |
| 10 | Background fleet processing | ~400 lines | None |

**All PRs can be developed in parallel** - they touch independent systems.

---

## Code Standards

All code must:
1. Follow [CONTRIBUTING.md](/CONTRIBUTING.md) guidelines
2. Pass PHPStan: `./vendor/bin/phpstan analyse`
3. Pass PSR-12: `./vendor/bin/pint`
4. Pass tests: `php artisan test`
5. **Keep GitHub history clean** - no AI/assistant mentions in commits or PRs
