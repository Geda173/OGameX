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

### PR 10: Fleet Queue System
**Size**: ~200-300 lines
**System**: FleetController, FleetMissionService, Migration

**Problem**: `time_arrival` is Unix timestamp (seconds). Multiple fleets arriving at the same second have non-deterministic processing order. This matters for ninja defenses, timed attacks, etc.

**Solution**: Millisecond arrival time precision

Store calculated arrival time with millisecond granularity so fleets are processed in actual arrival order, not dispatch order.

```php
// Migration: Add millisecond precision column
$table->bigInteger('time_arrival_ms')->default(0); // Unix timestamp in milliseconds

// At dispatch time (FleetController)
$arrivalTimeMs = (int)(($departureTime + $flightDuration) * 1000); // or use microtime calculation
$mission->time_arrival_ms = $arrivalTimeMs;

// Processing order
->orderBy('time_arrival')
->orderBy('time_arrival_ms')
```

**Why not FIFO (dispatch order)?**
- FIFO rewards who clicked "send" first, not actual arrival
- A deathstar dispatched first would beat a ninja defender dispatched later
- Millisecond arrival reflects the actual calculated physics of flight time

**Acceptance Criteria**:
- [ ] `time_arrival_ms` column added to `fleet_missions` table
- [ ] Arrival time calculated with ms precision at dispatch
- [ ] Fleet processing ordered by `time_arrival`, then `time_arrival_ms`
- [ ] Fleets with earlier calculated arrival process first
- [ ] Existing missions get sensible defaults (e.g., `time_arrival * 1000`)

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
| 10 | Millisecond arrival ordering | ~150 lines | None |

**All PRs can be developed in parallel** - they touch independent systems.

---

## Code Standards

All code must:
1. Follow [CONTRIBUTING.md](/CONTRIBUTING.md) guidelines
2. Pass PHPStan: `./vendor/bin/phpstan analyse`
3. Pass PSR-12: `./vendor/bin/pint`
4. Pass tests: `php artisan test`
5. **Keep GitHub history clean** - no AI/assistant mentions in commits or PRs
