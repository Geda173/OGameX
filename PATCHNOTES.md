# OGameX Patch Notes - November 2025

## Bug Fixes

### ACS Attack Fleet Synchronization Fix
**Issue**:
1. When joining an ACS Attack group with a slower fleet, users had to click multiple times due to timing errors
2. Slower fleets were incorrectly speeding up to match faster fleets instead of delaying the entire group
3. The 30% speed rule validation was happening in the wrong order, preventing valid slower fleets from joining

**Root Cause**:
The fleet synchronization logic was checking the 30% speed rule BEFORE determining if a slower fleet should delay the group. This meant:
- A slower fleet trying to join would calculate a very low "required speed" to match the current group arrival
- This low speed would fail the 30% rule check and be rejected
- The fleet never got to the logic that would properly delay the entire group

**Fix**:
Completely rewrote the ACS fleet synchronization logic in the correct order:

1. **Slower Fleet Joins (Delays Group)**:
   - First check if the new fleet's natural arrival > group's current arrival
   - Calculate the new group arrival time (delayed to match slower fleet)
   - Validate that delaying doesn't violate 30% rule for any existing fleet members
   - If valid, delay ALL existing fleets in the group to match the slower fleet
   - New fleet flies at 100% speed

2. **Faster Fleet Joins (Slows Down)**:
   - Calculate required speed to match current group arrival
   - Validate against 30% rule (required speed >= slowest group member - 30%)
   - If valid, adjust the new fleet's speed to match group
   - Use delayed departure if needed for very close targets

**Impact**:
- Slower fleets now correctly delay the entire group (OGame standard behavior)
- 30% speed rule is properly enforced in both directions
- Clear error messages explain why a fleet cannot join
- No more timing errors requiring multiple clicks
- Detailed logging for debugging synchronization issues

**Files Changed**:
- `app/Http/Controllers/FleetController.php` - Rewrote ACS synchronization logic (lines 457-632)

### ACS Defend Mission Processing Fix
**Issue**: When an ACS Defend mission participated in combat or completed its hold duration, the application would crash with "PlanetServiceFactory::make(): Argument #1 ($planetId) must be of type int, null given" error.

**Root Cause**:
1. **Incorrect Target Type**: ACS Defend missions were incorrectly set to `PlanetType::DeepSpace` (like Expeditions) instead of keeping their original target type (Planet or Moon). This caused `planet_id_to` to never be set.
2. **Query Not Finding Missions**: The `getDefendingMissionsAtPlanet()` method only searched by `planet_id_to`, so it couldn't find missions with null planet_id_to
3. **Type Casting Issues**: Fleet mission records were missing proper type casting in the Eloquent model

**Fix**:
1. **Fixed Target Type Logic**: Changed `GameMission::start()` to only set target type to DeepSpace for Expeditions. ACS Defend missions now keep their original Planet/Moon target type, ensuring `planet_id_to` is properly set
2. **Enhanced Query**: `getDefendingMissionsAtPlanet()` now searches by both `planet_id_to` AND coordinates as a fallback for legacy missions
3. **Defensive Loading**: `ACSDefendMission::processArrival()` now has fallback logic to load target planet by coordinates if `planet_id_to` is null
4. **Added Type Casting**: FleetMission model now explicitly casts `mission_type`, `parent_id`, `user_id`, `planet_id_from`, `planet_id_to`, `processed`, and `canceled` fields to integers
5. **Direct Class Resolution**: Battle processing now resolves `ACSDefendMission` class directly instead of using `GameMissionFactory::getMissionById()`
6. **Improved Validation**:
   - `FleetMissionService::updateMission()` - Uses `is_numeric()` and `intval()` for more flexible type validation
   - `GameMission::startReturn()` - Validates parent mission_type before creating return missions
7. **Error Handling**: Added try-catch blocks and detailed logging to prevent application crashes
8. **Graceful Degradation**: Invalid missions are logged and marked as processed to prevent retry loops

**Impact**:
- New ACS Defend missions are created with correct target type and planet_id_to
- Existing broken missions can still be processed via coordinate fallback
- Defending fleets participate in battles correctly
- Return missions are created successfully after battles or hold duration
- Detailed logging helps identify any future issues

**Files Changed**:
- `app/Models/FleetMission.php` - Added type casting
- `app/GameMissions/Abstracts/GameMission.php` - Fixed target type logic for ACS Defend
- `app/GameMissions/ACSDefendMission.php` - Added defensive planet loading
- `app/Services/FleetMissionService.php` - Enhanced query and validation
- `app/GameMissions/AttackMission.php` - Direct class resolution for return missions
- `app/GameMissions/ACSAttackMission.php` - Direct class resolution for return missions

## Major Features

### Alliance Combat System (ACS)

The complete Alliance Combat System has been implemented, allowing coordinated fleet operations between alliance members and buddies.

#### ACS Attack Mission (Mission Type 2)
- **Coordinated Attacks**: Multiple players can coordinate attacks on the same target with all fleets combining into a single battle force
- **Group Management**:
  - Maximum 16 fleets per group
  - Maximum 5 unique players per group
  - Custom group names for better organization
  - Real-time participant tracking in fleet events
- **Loot Distribution**: Loot is distributed proportionally based on surviving cargo capacity after battle
- **Battle Reports**: Single unified battle report sent to all participants
- **Restrictions**: Only buddies and alliance members can participate
- **Fleet Synchronization**: Automatic 30% speed rule enforcement for fleet coordination

#### ACS Defend Mission (Mission Type 5)
- **Defensive Operations**: Send fleets to defend buddy or alliance member planets
- **Hold Duration**: Support missions can hold for up to 32 hours at the target location
- **Deuterium Consumption**: Calculated hourly based on fleet composition
- **Alliance Depot Support**: Each level provides 20,000 deuterium per hour to reduce consumption
- **Automatic Participation**: Defending fleets automatically participate in any battles at the location
- **Return Trip**: Surviving fleets automatically return home after hold duration expires
- **Validation**: Full validation of hold time limits and deuterium requirements before dispatch

#### ACS Invitation System
- **In-Game Invitations**: Group creators can invite buddies and alliance members to join ACS groups
- **Message Integration**: Invitations sent via in-game messaging system with clickable coordinates
- **Group Information**: Invitations include group name, target, and participant details
- **Status Tracking**: Invitations track pending/joined/declined status
- **Automatic Cleanup**: Expired invitations are automatically cleaned up when groups complete or are cancelled

#### ACS UI/UX Improvements
- **Fleet Conversion**: Convert regular attack missions to ACS attacks with custom group naming
- **Player Selection**: Dropdown interface for inviting buddies and alliance members
- **Enhanced Fleet Events**: Fleet displays show ACS group information and participant lists
- **Modal Dialogs**: Clean in-game modals replace browser prompts
- **Color-Coded Display**: Mission status visually distinguished with color classes

### Buddy System

A complete buddy management system for establishing player relationships outside of alliances.

#### Core Features
- **Buddy Requests**: Send and receive buddy requests to other players
- **Request Management**: Accept or decline pending buddy requests
- **Buddy List**: View all your current buddies
- **Integration**: Buddies can participate in ACS operations together
- **Database Schema**: Full migration support for buddy requests and relationships

#### Implementation Details
- Models: `Buddy.php`, `BuddyRequest.php`
- Service Layer: `BuddyService.php` with 236 lines of buddy management logic
- Database tables: `buddy_requests`, `buddies`
- Controller integration with fleet and galaxy systems

### Phalanx Scanner

Complete sensor phalanx implementation for scanning fleet movements.

#### Scanner Capabilities
- **Fleet Detection**: Scan all incoming and outgoing fleet movements at target coordinates
- **Detailed Information**: View ship composition, mission type, and arrival times
- **Ship Tooltips**: Hover tooltips showing exact ship quantities
- **Real-Time Countdowns**: Live countdown timers for fleet arrivals
- **Range Calculation**: Phalanx range formula: (level^2) - 1
- **Deuterium Cost**: 5,000 deuterium per system distance from scanning moon

#### Scanner Features
- **Range Validation**: Ensures target is within phalanx range before scanning
- **Moon Requirement**: Automatically finds moons with phalanx capability in range
- **Direction Display**: Clearly shows incoming vs outgoing fleet movements
- **Coordinate Display**: Shows both origin and destination for scanned fleets
- **Return Trip Filtering**: Return trips correctly excluded from scans per OGame mechanics

#### UI Integration
- Galaxy view integration with phalanx icon/button
- AJAX-powered scanning with no page reload
- Styled overlay showing scan results
- Ship composition tooltips
- Countdown timers for fleet arrivals

## Critical Bug Fixes

### ACS Loot Distribution Fix
**Problem**: Loot was distributed based on original cargo capacity instead of surviving cargo capacity after battle.

**Example of Bug**:
- Player A: 100 Large Cargo, loses 50 in battle (50 survive)
- Player B: 50 Large Cargo, loses 0 (50 survive)
- OLD: Split 100:50 ratio (66.67% to A, 33.33% to B) - UNFAIR
- NEW: Split 50:50 ratio (50% each) - FAIR

**Solution**: Two-pass algorithm now calculates surviving cargo capacity first, then distributes loot proportionally based on actual surviving capacity.

**Impact**: Ensures fair loot distribution in all ACS attack scenarios.

### Resource Return Trip Fix
**Problem**: Critical data loss bug where resources loaded at mission departure were deleted and replaced by newly acquired resources instead of being added together.

**Examples of Data Loss**:

1. **Debris Field Harvesting**:
   - Load 1,000 metal on recyclers before departure
   - Harvest 500 crystal from debris field
   - OLD: Return with 500 crystal only (1,000 metal LOST)
   - NEW: Return with 1,000 metal + 500 crystal

2. **Expedition Missions**:
   - Load 10,000 deuterium on departure
   - Find 5,000 metal during expedition
   - OLD: Return with 5,000 metal only (10,000 deuterium LOST)
   - NEW: Return with 10,000 deuterium + 5,000 metal

3. **Attack Missions**:
   - Send attack with 20,000 metal loaded
   - Loot 10,000 crystal from enemy
   - OLD: Return with 10,000 crystal only (20,000 metal LOST)
   - NEW: Return with 20,000 metal + 10,000 crystal

**Root Cause**: The code had a TODO comment acknowledging the issue - resources from parent mission were being replaced instead of added.

**Solution**: Modified `GameMission.php` to add parent mission resources to new resources:
```php
// OLD (replaced):
$mission->metal = (int)$resources->metal->get();

// NEW (added):
$mission->metal = $parentMission->metal + (int)$resources->metal->get();
```

**Affected Mission Types**: RecycleMission, ExpeditionMission, AttackMission, ACSAttackMission, TransportMission, DeploymentMission, ColonizationMission.

### Phalanx Query Fix
**Problem**: WHERE clause grouping was incorrect, showing missions to/from the scanning planet instead of only the target planet.

**Solution**: Properly grouped OR conditions with parentheses:
```sql
WHERE (galaxy_to=X AND system_to=Y AND position_to=Z) OR
      (galaxy_from=X AND system_from=Y AND position_from=Z)
```

**Impact**: Phalanx scans now correctly show only fleets related to the target coordinates.

### Phalanx Return Trip Filter
**Problem**: Return trips were appearing in phalanx scans, which violates OGame mechanics.

**Solution**: Added `whereNull('parent_id')` filter to exclude return missions from scan results.

**Impact**: Phalanx scanner now correctly shows only outgoing missions per OGame rules.

### ACS Fleet Recall Recalculation
**Problem**: When a fleet in an ACS group was recalled, the group's arrival time was not recalculated for remaining fleets.

**Solution**: Implemented automatic recalculation of group arrival time when fleets are recalled, updating all remaining fleets to arrive at the new optimal synchronized time.

**Impact**: ACS groups maintain proper synchronization even when participants recall their fleets.

### ACS Invitation Cleanup
**Problem**: Pending invitations remained in "pending" status forever after an ACS group completed or was cancelled, creating database clutter.

**Scenario**:
1. Player A creates ACS group and invites Player B
2. Before Player B responds, the attack completes or is cancelled
3. Invitation stays "pending" forever

**Solution**: Added automatic cleanup that deletes pending invitations when groups complete or are cancelled via `cleanupInvitations()` method. Keeps "joined" and "declined" invitations for history.

**Impact**: Prevents stale invitation buildup and reduces database clutter.

### ACS Defend Validation
**Problem**: MAX_HOLD_HOURS = 32 was defined but not enforced at fleet dispatch.

**Solution**: Added validation that checks `holding_hours <= 32` for ACS Defend missions before allowing dispatch.

**Error Message**: "ACS Defend hold time cannot exceed 32 hours. Requested: X hours."

**Impact**: Prevents invalid fleet missions that exceed OGame mechanics limits.

### ACS Defend Deuterium Validation
**Problem**: Players could dispatch ACS Defend missions without sufficient deuterium for the hold duration.

**Solution**: Added comprehensive deuterium validation that:
- Calculates total deuterium required for hold duration
- Accounts for Alliance Depot supply (20,000 deut/hour per level)
- Shows detailed error messages with shortfall amounts
- Validates before creating the mission

**Impact**: Prevents mission failures due to insufficient deuterium.

## Ship & Cargo Improvements

### Large Cargo Speed Fix
**Problem**: Large Cargo ships had incorrect base speed of 15,000 instead of 7,500.

**Solution**: Corrected base speed in `CivilShipObjects.php` from 15,000 to 7,500.

**Impact**: Large Cargo ships now have correct speed matching OGame mechanics.

### Hyperspace Technology Cargo Bonus
**Problem**: Hyperspace Technology was not properly increasing cargo capacity.

**Solution**: Added proper calculation for bonus cargo capacity based on Hyperspace Technology level in `CapacityPropertyService.php`.

**Impact**: Hyperspace Technology research now correctly increases cargo capacity of ships.

## Database Optimizations

### ACS Groups Coordinate Index
**Added**: Composite index on `(galaxy_to, system_to, position_to)` in `acs_groups` table.

**Impact**: Significantly faster lookups when finding ACS groups by target coordinates.

### ACS Transaction Locking
**Added**: Database transactions with row locking to prevent race conditions in ACS group operations.

**Impact**: Prevents duplicate groups and ensures data consistency during concurrent ACS operations.

## Battle System Improvements

### Duplicate Battle Report Fix
**Problem**: Multiple battle reports were being generated for ACS attacks.

**Solution**: Modified battle report generation to create a single report for the entire ACS group battle.

**Impact**: All ACS participants receive the same unified battle report ID.

### First Round Destruction Handling
**Problem**: No proper message when attacking fleets were destroyed in the first combat round.

**Solution**: Added "Fleet Lost Contact" message system that notifies attackers when their fleet is destroyed in the first round before establishing a battle report.

**Implementation**:
- New message type: `FleetLostContact`
- Feature test coverage for first-round destruction scenarios
- Proper message styling and subject line

## UI/UX Improvements

### Fleet Event Display
- Color-coded mission status indicators
- Mission type display with proper CSS classes
- ACS group participant tracking
- Dynamic mission colors based on status

### Event List Component
- Enhanced event row display with ACS information
- Proper handling of ACS Defend missions
- Visual distinction between own fleets and friendly fleets
- Improved time calculations and countdown displays

### Fleet Overview
- Fixed 500 errors caused by deleted fleet missions
- Proper null checking for ACS group references
- Restored mission status logic
- Added `getDefendingMissionsAtPlanet` method to `FleetMissionService`

### Mission Color Coding
- Added `.own` color class globally for own missions
- Color classes for eventbox mission type display
- Proper mission_status classes on fleet event rows
- Consistent color scheme across all fleet displays

## Technical Improvements

### Code Quality
- PSR-12 compliance via Pint formatting
- Proper PHP blocks replacing inline Blade directives
- Time calculations moved to PHP blocks for reliability
- Critical type casting fixes (time values cast to int)

### Service Layer Enhancements
- `ACSService.php`: 328 lines of ACS management logic
- `FleetHoldConsumptionService.php`: 111 lines of deuterium consumption calculations
- `BuddyService.php`: 236 lines of buddy management
- `PlayerService.php`: Added phalanx-related helper methods

### Message System
- New message factory classes for game messages
- ACS invitation message type
- Fleet lost contact message type
- Improved message dispatch logic

### Database Schema
New tables added:
- `acs_groups`: Group metadata and target coordinates
- `acs_fleet_members`: Fleet to group relationships (unique constraint)
- `acs_invitations`: Invitation status tracking (with expired status)
- `buddy_requests`: Buddy request management
- `buddies`: Buddy relationships

## Routes Added
- ACS group management endpoints
- ACS invitation endpoints
- Phalanx scan endpoint
- Buddy system endpoints

## Testing

### Feature Test Coverage
- Combat report creation tests
- First round destruction scenarios
- Contact lost message tests
- Attacker destroyed in first round coverage

## Files Modified/Created

**Core ACS Files** (3,433+ lines added):
- `app/GameMissions/ACSAttackMission.php` (638 lines)
- `app/GameMissions/ACSDefendMission.php` (193 lines)
- `app/Services/ACSService.php` (328 lines)
- `app/Services/FleetHoldConsumptionService.php` (111 lines)

**Buddy System** (523 lines added):
- `app/Models/Buddy.php` (43 lines)
- `app/Models/BuddyRequest.php` (46 lines)
- `app/Services/BuddyService.php` (236 lines)

**Phalanx System** (500+ lines added):
- `app/Http/Controllers/GalaxyController.php` (phalanx methods)
- `app/Services/PlayerService.php` (phalanx helper methods)
- `resources/views/ingame/galaxy/index.blade.php` (phalanx UI)

**Controllers & UI**:
- `app/Http/Controllers/FleetController.php` (+962 lines)
- `app/Http/Controllers/FleetEventsController.php`
- `resources/views/ingame/fleetevents/*.blade.php`
- `resources/views/ingame/fleet/index.blade.php`

**Migrations**: 5 new migrations for ACS tables, 2 for buddy system

**Models**: AcsGroup, AcsFleetMember, AcsInvitation, Buddy, BuddyRequest

## Summary

This release represents a major milestone for OGameX with the complete implementation of:
- Alliance Combat System (ACS Attack & Defend)
- Buddy System for player relationships
- Phalanx Scanner for fleet intelligence
- Critical resource handling fixes affecting all mission types
- Major performance optimizations and bug fixes

The update includes over 4,500 lines of new production-ready code, comprehensive database schema additions, and extensive UI improvements. All features are fully integrated with existing game mechanics and include proper validation, error handling, and optimization.
