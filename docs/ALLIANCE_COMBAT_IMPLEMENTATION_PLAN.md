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

### PR 8: Loot Distribution & Combat Mechanics
**Size**: ~400-600 lines

**Code TODOs to address**:
```
app/GameMissions/AttackMission.php:230  - Per-fleet loot share (cargo capacity proportional)
app/GameMissions/AttackMission.php:234  - Reaper debris collection in multi-attacker
app/GameMissions/BattleEngine/BattleEngine.php:68 - Per-fleet cargo capacity calculation
app/Services/PhalanxService.php:319     - Show ACS Attack (type 2) in Phalanx scan
app/GameMissions/EspionageMission.php:100 - ACS Defend in counter-espionage chance
app/GameMissions/EspionageMission.php:465 - ACS Defend fleets in espionage report
```

**Acceptance Criteria**:
- [ ] Loot distributed proportionally to surviving cargo capacity per fleet
- [ ] Fleet with no cargo gets no loot
- [ ] Reaper debris collection works in multi-attacker battles
- [ ] Phalanx shows ACS Attack fleets in scan results
- [ ] ACS Defend fleets contribute to counter-espionage chance
- [ ] ACS Defend fleets appear in espionage reports

---

### PR 9: Enhanced Battle Reports
**Size**: ~600-900 lines

**Code TODOs to address**:
```
app/GameMissions/AttackMission.php:412  - Send report to ALL participating players
app/GameMissions/AttackMission.php:672  - Per-fleet breakdown in battle reports
app/GameMessages/BattleReport.php:143   - Multi-attacker display in report template
```

**Acceptance Criteria**:
- [ ] Battle report sent to all participating attackers (not just initiator)
- [ ] Report shows all attacker fleets with individual tech levels
- [ ] Report shows defender + ACS Defend fleets separately
- [ ] Fleet filter dropdown in report view
- [ ] Player receives one report even with multiple fleets

---

### PR 10: Fleet Queue System
**Size**: ~300-500 lines

**Problem**: `time_arrival` is stored as Unix timestamp (seconds). Multiple fleets arriving at the same second have non-deterministic processing order.

**Recommended Solution**: Laravel Queue Worker
```php
// When fleet is dispatched
ProcessFleetArrival::dispatch($fleetMissionId)
    ->delay(Carbon::createFromTimestamp($arrivalTime));
```

**Benefits**:
- Natural FIFO ordering (first dispatched = first processed)
- No database schema changes needed
- Processes at exact arrival time (no polling delay)
- OGameX already has queue infrastructure

**Key Implementation Points**:
- Create `app/Jobs/ProcessFleetArrival.php`
- Dispatch delayed job in `FleetController` at fleet send
- Cancel job on fleet recall
- Keep scheduler as fallback for missed jobs

**Acceptance Criteria**:
- [ ] Fleets arriving at same second process in dispatch order
- [ ] Job dispatched with correct delay when fleet sent
- [ ] Job cancelled when fleet recalled
- [ ] Scheduler fallback catches missed jobs

---

## Summary

| Phase | Status |
|-------|--------|
| ACS Defend (basic + depot) | ✅ Complete |
| ACS Attack (unions + battles) | ✅ Complete |
| Server setting enforcement | ✅ Complete |
| Loot distribution | Ready |
| Battle reports | Ready |
| Fleet queue system | Ready |

**Remaining PRs can be developed in parallel** - they touch different code paths.

---

## Code Standards

All code must:
1. Follow [CONTRIBUTING.md](/CONTRIBUTING.md) guidelines
2. Pass PHPStan: `./vendor/bin/phpstan analyse`
3. Pass PSR-12: `./vendor/bin/pint`
4. Pass tests: `php artisan test`
5. **Keep GitHub history clean** - no AI/assistant mentions in commits or PRs
