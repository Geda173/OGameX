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
- **Per-fleet loot distribution** — loot split proportionally by surviving cargo
  capacity per fleet, carried resources survive at cargo survival rate
  (`BattleEngine::distributeLoot*`, `AttackerFleetResult->lootShare`/`survivingCargo`)

### Useful existing building blocks
- `GameMission::collectDefendingFleets()` (`app/GameMissions/Abstracts/GameMission.php:637`) —
  gathers planet owner + all ACS Defend fleets holding at a planet. Reuse for espionage work.
- `CharacterClassService::getReaperDebrisCollectionPercentage()` returns 30% for ALL classes —
  only *building* Reapers is General-exclusive. Collection is not class-gated.
- Single-attacker Reaper collection reference implementation: `AttackMission.php:300-330`.

---

## Remaining Work

### PR 8b: Reaper Debris in Multi-Attacker Battles
**Size**: ~80-120 lines
**Code TODO**: `app/GameMissions/AttackMission.php:244`

**Scope**: Reaper auto-collection currently only runs in the single-attacker path.
Extend it to the multi-fleet result loop.

**How**:
1. After battle, compute total collectable debris (30% cap) once — same rules as
   the single-attacker path at `AttackMission.php:300-330`.
2. For each surviving fleet with Reapers, compute that fleet's Reaper cargo capacity
   using the fleet owner's own tech (`capacity->calculate($fleetOwner)`).
3. Split the collectable debris across fleets proportionally to Reaper capacity,
   capped per fleet at its capacity.
4. Add each share to that fleet's return resources *before* the cargo-capacity clamp
   at `AttackMission.php:253-257`, deduct the total from the debris field, and send
   the Reaper collection message per player.

**Acceptance Criteria**:
- [ ] Any fleet with surviving Reapers collects debris (not class-gated)
- [ ] Debris split proportionally to per-fleet Reaper cargo capacity
- [ ] Total collected never exceeds 30% of debris field
- [ ] Collected amount deducted from the debris field; per-player message sent
- [ ] Single-attacker behavior unchanged

---

### PR 8c: Phalanx ACS Attack Display
**Size**: ~30-50 lines
**Code TODO**: `app/Services/PhalanxService.php:324`

**Scope**: ACS Attack (type 2) fleets are mislabeled in Phalanx scans.
`getFleetDirectionLabel()` only treats type 1 as "Enemy fleet", so type 2 falls
through to "Friendly fleet". Each union member has its own `fleet_missions` row,
so fleets already appear individually — the labeling and return-trip handling
just need to cover type 2.

**How**: Treat mission type 2 like type 1 in `getFleetDirectionLabel()`; verify
`missionHasReturnTrip()` and the scan loop handle type 2 rows; add a Phalanx test
with a union of two attackers.

**Acceptance Criteria**:
- [ ] ACS Attack fleets labeled "Enemy fleet" in Phalanx scan
- [ ] Each union member fleet shown as its own row with correct arrival time
- [ ] Return trips of union members displayed like regular attack returns

---

### PR 8d: ACS Defend in Espionage
**Size**: ~100-150 lines
**Code TODOs**: `app/GameMissions/EspionageMission.php:113` and `:498`

**Scope**: ACS Defend fleets holding at a planet are invisible to espionage —
they don't raise counter-espionage chance and don't appear in reports, yet they
fight in battles.

**How**:
1. Counter-espionage (line 113): include ships from holding ACS Defend fleets in
   `$defenderShipCount`. Reuse the holding-fleet query from
   `GameMission::collectDefendingFleets()` (consider extracting it into
   `FleetMissionService` so both call sites share it).
2. Report (line 498): when ships are revealed, append ACS Defend fleet units.
   Decide display: merged into ship totals (simplest, matches "ships at planet")
   or a separate section with owner labels (closer to OGame). Start merged;
   owner-labeled display can follow in the report UI PR.

**Acceptance Criteria**:
- [ ] Holding ACS Defend ships counted in counter-espionage chance
- [ ] Holding ACS Defend ships visible in espionage report ships section
- [ ] Fleets no longer holding (departed/recalled) are excluded

---

### PR 9a: Battle Report to All Participants
**Size**: ~50-100 lines
**Code TODO**: `app/GameMissions/AttackMission.php:441`

**Scope**: Only the union initiator and the defender receive the battle report.

**How**: At the send site (`AttackMission.php:441-460`), collect unique owner IDs
from `$battleResult->attackerFleetResults` and send the report to each. Keep the
"destroyed in first round" rule per player: a player whose fleets were all lost
in round one gets the simplified `FleetLostContact` message instead of the full
report. One report per player even if they sent multiple fleets.

**Acceptance Criteria**:
- [ ] All union members receive the battle report (or lost-contact message)
- [ ] Exactly one message per participating player
- [ ] Defender behavior unchanged

---

### PR 9b: Per-Fleet Battle Report Display
**Size**: ~200-300 lines
**Code TODOs**: `app/GameMissions/AttackMission.php:699`, `app/GameMessages/BattleReport.php:145`

**Scope**: Reports show one aggregated attacker and one aggregated defender.
Multi-fleet battles should show each participating fleet.

**How**:
1. Persist per-fleet data when creating the report (`createBattleReport`,
   around `AttackMission.php:699`): store `attackerFleetResults` /
   `defenderFleetResults` (owner, units start/result/lost, tech levels) in the
   report model alongside the existing aggregate fields — keep aggregates for
   backward compatibility with old reports.
2. Render in `BattleReport.php` + `battle_report_full.blade.php`: keep combined
   totals as the default view, add expandable per-fleet sections (planet owner's
   forces, each ACS Defend fleet, each attacker fleet) with owner names and tech.
3. Old reports without per-fleet data must render exactly as before.

**Acceptance Criteria**:
- [ ] Report stores per-fleet breakdown for multi-fleet battles
- [ ] Template shows each attacker/defender fleet with owner and tech levels
- [ ] Combined totals still shown; single-fleet and legacy reports unchanged

---

### PR 9c: Union Arrival Time Preview (small UI)
**Size**: ~30-60 lines
**Code TODO**: `resources/views/ingame/fleet/index.blade.php:1131`

**Scope**: When joining a union in fleet dispatch, show the player the
synchronized arrival time (union arrival, possibly delayed by their join) before
they confirm. Backend already returns union arrival data via the available-unions
endpoint; surface it in the dispatch UI (step 3).

---

### PR 10: Fleet Background Processing System
**Size**: ~400-500 lines
**Status**: Not started. (Admin "stuck fleet mission" tooling was added in the
meantime — a workaround that this PR would make largely unnecessary.)

**Problems to solve** (matches the open GitHub issue on background processing):
1. Processing depends on page loads/polling — not real-time, misses 1-3s target
2. Fleets arriving in the same second have non-deterministic order
3. Everything is sequential — non-conflicting planets can't process concurrently
4. Large battles previously needed PHP timeouts raised to 300s; background
   workers would let those be reduced again

**Solution: Laravel delayed jobs (timing) + millisecond column (ordering) + per-planet locks (conflicts)**

Part 1 — ordering:
```php
// Migration
$table->bigInteger('time_arrival_ms')->default(0);
// Backfill existing rows: time_arrival * 1000

// At dispatch
$mission->time_arrival_ms = (int)(($departureTime + $flightDurationExact) * 1000);
```

Part 2 — timing + conflict isolation:
```php
// At dispatch
ProcessFleetArrival::dispatch($mission->id)
    ->delay(Carbon::createFromTimestamp($mission->time_arrival));

// Job: lock per target planet, drain all due missions in ms order
Cache::lock("fleet-process:planet:{$targetPlanetId}", 30)->block(10, function () {
    FleetMission::where(...due...)->orderBy('time_arrival')
        ->orderBy('time_arrival_ms')->get()
        ->each(fn ($m) => $this->fleetMissionService->updateMission($m));
});
```

Concurrency model: different target planets run in parallel; same planet is
serialized under the lock and drained in millisecond order — an attack and a
ninja defense arriving in the same second resolve by actual computed arrival.

Edge cases:
- Recall: mark mission canceled — the job checks state on wake and no-ops
  (simpler and safer than deleting queue rows)
- Downtime: keep the existing scheduler as fallback sweep for missed jobs
- Errors: failed jobs land in `failed_jobs` with logging; lock timeout prevents deadlock

**Migration strategy** (from the issue's "gradually migrate" note):
1. Ship the ms column + ordering change alone (safe, immediate fairness fix)
2. Add the job dispatch alongside the existing polling (both paths idempotent
   via `processed` flag + lock)
3. Once stable, demote polling to a low-frequency fallback sweep and lower PHP timeouts

**Acceptance Criteria**:
- [ ] `time_arrival_ms` stored at dispatch; processing ordered by it
- [ ] Jobs fire within 1-3s of arrival time without any user online
- [ ] Same-planet missions serialized in arrival order; different planets concurrent
- [ ] Recalled missions are not processed by pending jobs
- [ ] Fallback sweep recovers after queue-worker downtime
- [ ] Battle processing no longer bound to web request timeouts

---

## Summary

| PR | Focus | Size | Dependencies |
|----|-------|------|--------------|
| 8b | Reaper debris multi-attacker | ~100 lines | None |
| 8c | Phalanx display | ~30 lines | None |
| 8d | Espionage integration | ~100 lines | None |
| 9a | Reports to all players | ~50 lines | None |
| 9b | Per-fleet report display | ~200 lines | 9a (optional) |
| 9c | Union arrival preview UI | ~50 lines | None |
| 10 | Background fleet processing | ~400 lines | None (3-step rollout) |

**All PRs can be developed in parallel** — they touch independent systems.

---

## Code Standards

All code must:
1. Follow [CONTRIBUTING.md](/CONTRIBUTING.md) guidelines
2. Pass PHPStan: `./vendor/bin/phpstan analyse` (now level 8)
3. Pass PSR-12: `./vendor/bin/pint`
4. Pass tests: `php artisan test`
5. **Keep GitHub history clean** — no AI/assistant mentions in commits or PRs
