<?php

namespace OGame\Services;

use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use OGame\GameObjects\Models\Calculations\CalculationType;
use OGame\Models\FleetMission;
use OGame\Models\Planet;
use OGame\Models\Resources;
use OGame\Models\User;
use OGame\Models\UserTech;
use RuntimeException;
use Throwable;

/**
 * Class PlayerService.
 *
 * Player object.
 *
 * @package OGame\Services
 */
class PlayerService
{
    /**
     * The planet list object for this player.
     *
     * @var PlanetListService
     */
    public PlanetListService $planets;

    /**
     * The user object from the model of this player.
     *
     * @var User
     */
    private User $user;

    /**
     * The user tech object from the model of this player.
     *
     * @var UserTech
     */
    private UserTech $user_tech;

    /**
     * Private local cached general score for this player.
     *
     * @var int|null
     */
    private int|null $cachedGeneralScore = null;

    /**
     * Player constructor.
     *
     * @param int $player_id
     */
    public function __construct(int $player_id = 0)
    {
        // Load the player object if a positive player ID is given.
        if ($player_id !== 0) {
            $this->load($player_id);
        } else {
            // If no player ID is given then an actual player context will not be available.
            // This is expected for unittests, that's why we create a dummy user object here.
            $this->user = new User();
            $this->user->id = 0;
            $this->planets = resolve(PlanetListService::class, ['player' => $this]);
        }
    }

    /**
     * Checks if this object is equal to another object.
     *
     * @param PlayerService|null $other
     * @return bool
     */
    public function equals(PlayerService|null $other): bool
    {
        return $other !== null && $this->getId() === $other->getId();
    }

    /**
     * Load player object by user ID.
     *
     * @param int $id
     */
    public function load(int $id): void
    {
        // Fetch user from model
        $user = User::where('id', $id)->first();

        // Handle case where user doesn't exist (e.g., deleted account)
        if ($user === null) {
            throw new RuntimeException('User not found with ID: ' . $id);
        }

        $this->user = $user;

        // Fetch user tech from model
        /** @var UserTech $tech */
        $tech = $this->user->tech()->first();
        if (!$tech) {
            $tech = new UserTech();
            $tech->user_id = $user->id;
            $tech->save();
        }
        $this->setUserTech($tech);

        // Fetch all planets of user
        $planet_list_service = resolve(PlanetListService::class, ['player' => $this]);
        $this->planets = $planet_list_service;
    }

    /**
     * Checks is the supplied password is valid for this user. This method is used as
     * a security measure for critical operations like abandoning a planet.
     *
     * @param string $password
     * @return bool
     */
    public function isPasswordValid(string $password): bool
    {
        return Auth::attempt(['email' => $this->getEmail(), 'password' => $password]);
    }

    /**
     * Set user tech object.
     *
     * @param UserTech $userTech
     * @return void
     */
    public function setUserTech(UserTech $userTech): void
    {
        $this->user_tech = $userTech;
    }

    /**
     * Get current player ID.
     */
    public function getId(): int
    {
        return $this->user->id;
    }

    /**
     * Get the user model instance.
     *
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * Saves current player object to DB.
     */
    public function save(): void
    {
        $this->user->save();
    }

    /**
     * Checks if the player is an admin.
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->user->hasRole('admin');
    }

    /**
     * Checks if the player is inactive.
     *
     * @return bool
     */
    public function isInactive(): bool
    {
        $lastActivity = Carbon::createFromTimestamp((int)$this->user->time);

        // If the player has not logged in in the last 7 days, then they are considered inactive.
        if ($lastActivity->diffInDays(now()) >= 7) {
            return true;
        }

        return false;
    }

    /**
     * Checks if the player is long inactive.
     *
     * @return bool
     */
    public function isLongInactive(): bool
    {
        $lastActivity = Carbon::createFromTimestamp((int)$this->user->time);

        // If the player has not logged in in the last 28 days, then they are considered long inactive.
        if ($lastActivity->diffInDays(now()) >= 28) {
            return true;
        }

        return false;
    }

    /**
     * Checks if the player is a newbie (under noob protection).
     *
     * New noob protection rules:
     * - Under 50,000 points: Can only be attacked by players with max 5x more points
     * - 50,000 to 500,000 points: Can only be attacked by players with max 10x more points
     * - Over 500,000 points: No noob protection
     *
     * @param PlayerService $comparedTo The player to compare against
     * @return bool True if this player is protected from the compared player
     */
    public function isNewbie(PlayerService $comparedTo): bool
    {
        // Sanity check: if player is inactive (7+ days), they lose noob protection
        if ($this->isInactive()) {
            return false;
        }

        // Sanity check: if player is outlaw, they lose noob protection
        if ($this->isOutlaw()) {
            return false;
        }

        $currentPlayerPoints = $this->getCachedGeneralScore();
        $comparedToPoints = $comparedTo->getCachedGeneralScore();

        // No noob protection for players with 500,000+ points
        if ($currentPlayerPoints >= 500000) {
            return false;
        }

        // Under 50,000 points: protected from players with 5x or more points
        if ($currentPlayerPoints < 50000) {
            return $comparedToPoints > ($currentPlayerPoints * 5);
        }

        // 50,000 to 500,000 points: protected from players with 10x or more points
        if ($currentPlayerPoints < 500000) {
            return $comparedToPoints > ($currentPlayerPoints * 10);
        }

        return false;
    }

    /**
     * Checks if the player is strong compared to another player.
     * A player is "strong" if the other player would be under noob protection from them.
     *
     * @param PlayerService $comparedTo The player to compare against
     * @return bool True if this player is too strong to attack the compared player
     */
    public function isStrong(PlayerService $comparedTo): bool
    {
        // Simply check if the compared player is a newbie relative to us
        return $comparedTo->isNewbie($this);
    }

    /**
     * Checks if the player is currently outlaw (vogelfrei).
     * A player becomes outlaw for 7 days when they attack or spy on a strong player while under noob protection.
     *
     * @return bool True if the player is currently outlaw
     */
    public function isOutlaw(): bool
    {
        if (!$this->user->outlaw_until) {
            return false;
        }

        $outlawUntil = Carbon::parse($this->user->outlaw_until);

        // If the outlaw period has expired, clear it
        if ($outlawUntil->isPast()) {
            $this->user->outlaw_until = null;
            $this->user->save();
            return false;
        }

        return true;
    }

    /**
     * Marks the player as outlaw (vogelfrei) for 7 days.
     * This happens when a player under noob protection attacks or spies on a strong player.
     *
     * @return void
     */
    public function makeOutlaw(): void
    {
        $this->user->outlaw_until = now()->addDays(7);
        $this->user->save();
    }

    /**
     * Checks if military highscore exception applies for attacking a target.
     * Exception applies if:
     * - Players are within 100 places on military highscore, OR
     * - Target (defender) has more than 50% of attacker's military points
     *
     * This is called from the attacker's perspective: $attacker->hasMilitaryHighscoreException($target)
     *
     * @param PlayerService $target The target/defender player
     * @return bool True if military exception allows bypassing noob protection
     */
    public function hasMilitaryHighscoreException(PlayerService $target): bool
    {
        $attackerHighscore = \OGame\Models\Highscore::where('player_id', $this->getId())->first();
        $targetHighscore = \OGame\Models\Highscore::where('player_id', $target->getId())->first();

        if (!$attackerHighscore || !$targetHighscore) {
            return false;
        }

        // Check if within 100 places on military highscore
        $rankDifference = abs($attackerHighscore->military_rank - $targetHighscore->military_rank);
        if ($rankDifference <= 100) {
            return true;
        }

        // Check if target (defender) has more than 50% of attacker's military points
        // Per wiki: "wenn man mehr als 50% von seinen Militärpunkten hat"
        // = if the defender has more than 50% of the attacker's military points
        $attackerMilitaryPoints = $attackerHighscore->military ?? 0;
        $targetMilitaryPoints = $targetHighscore->military ?? 0;

        if ($attackerMilitaryPoints > 0 && $targetMilitaryPoints > ($attackerMilitaryPoints * 0.5)) {
            return true;
        }

        return false;
    }

    /**
     * Checks if this player can attack another player, considering all noob protection rules and exceptions.
     *
     * @param PlayerService|null $target The target player
     * @return bool True if attack is allowed
     */
    public function canAttack(?PlayerService $target): bool
    {
        // Can't attack null/destroyed planets with no owner
        if ($target === null) {
            return true; // Allow attacking destroyed planets
        }

        // Can't attack yourself
        if ($this->equals($target)) {
            return false;
        }

        // Military highscore exception bypasses noob protection
        if ($this->hasMilitaryHighscoreException($target)) {
            return true;
        }

        // Check if target is protected from us (they are a newbie relative to us)
        if ($target->isNewbie($this)) {
            return false;
        }

        // Check if we are protected from them (we are a newbie relative to them)
        // In this case, attacking them would make us outlaw, but it's still allowed
        // The outlaw status will be applied when the attack is sent
        return true;
    }

    /**
     * Set username property.
     *
     * @param string $username
     */
    public function setUsername(string $username): void
    {
        $this->user->username = $username;
        $this->user->username_updated_at = now();
    }

    /**
     * Validates a username.
     *
     * @param string $username
     * @return false|int
     */
    public function validateUsername(string $username): false|int
    {
        if (strlen($username) < 3) {
            return false;
        }

        return preg_match('/^[A-Za-z][A-Za-z0-9\s]*(?:_[A-Za-z0-9\s]+)*$/', $username);
    }

    /**
     * Validates if a username is already taken.
     *
     * @param string $username
     * @return bool
     */
    public function isUsernameAlreadyTaken(string $username): bool
    {
        return User::where('username', $username)->exists();
    }

    /**
     * Validates a username.
     *
     * @param string $username
     * @return array<string, mixed>
     */
    public function isUsernameValid(string $username): array
    {
        if (!$this->validateUsername($username)) {
            return [
                'valid' => false,
                'error' => __('Nickname :username contains invalid characters or your nickname has an invalid length!', ['username' => $username])
            ];
        }

        if ($this->isUsernameAlreadyTaken($username)) {
            return [
                'valid' => false,
                'error' => __('Player name already in use or invalid.')
            ];
        }

        return [
            'valid' => true,
            'error' => null
        ];
    }

    /**
     * Get the user's username.
     *
     * @param bool $formatted
     * @return string
     */
    public function getUsername(bool $formatted = true): string
    {
        if ($formatted && $this->isAdmin()) {
            return '<span class="status_abbr_admin">' . $this->user->username . '</span>';
        }
        return $this->user->username;
    }

    /**
     * Get the timestamp of the latest username change.
     *
     * @return Carbon|null
     */
    public function getLastUsernameChange(): Carbon|null
    {
        return $this->user->username_updated_at ? Carbon::parse($this->user->username_updated_at) : null;
    }

    /**
     * Set email address.
     *
     * @param string $email
     */
    public function setEmail(string $email): void
    {
        $this->user->email = $email;
    }

    /**
     * Validates whether input matches current users password.
     *
     * @param string $password
     * @return bool
     */
    public function validatePassword(string $password): bool
    {
        if (Auth::Attempt((['email' => $this->getEmail(), 'password' => $password]))) {
            return true;
        }

        return false;
    }

    /**
     * Get email address.
     *
     * @return string
     */
    public function getEmail(): string
    {
        return $this->user->email;
    }

    /**
     * Gets the level of a research technology for this player.
     *
     * @param string $machine_name
     * @return int
     */
    public function getResearchLevel(string $machine_name): int
    {
        $research = ObjectService::getResearchObjectByMachineName($machine_name);

        $research_level = $this->user_tech->{$research->machine_name} ?? 0;
        if ($research_level) {
            return $research_level;
        } else {
            return 0;
        }
    }

    /**
     * Set the level of a research technology for this player.
     *
     * @param string $machine_name
     * @param int $level
     * @param bool $save_to_db
     * @return void
     */
    public function setResearchLevel(string $machine_name, int $level, bool $save_to_db = true): void
    {
        $research = ObjectService::getResearchObjectByMachineName($machine_name);
        $this->user_tech->{$research->machine_name} = $level;

        if ($save_to_db) {
            $this->user_tech->save();
        }
    }

    /**
     * Get planet ID that player has currently selected / is looking at.
     *
     * @return int
     */
    public function getCurrentPlanetId(): int
    {
        if (!$this->user->planet_current) {
            // If no current planet is set, return the first planet of the player.
            return $this->planets->first()->getPlanetId();
        }

        return $this->user->planet_current;
    }

    /**
     * Set current planet ID (update).
     *
     * @param int $planet_id
     */
    public function setCurrentPlanetId(int $planet_id): void
    {
        // Check if user owns this planet ID.
        // Planet ID 0 is always valid as that will be updated to the first planet of the player.
        if ($planet_id == 0) {
            $this->user->planet_current = null;
            $this->user->save();
            return;
        } elseif ($this->planets->planetExistsAndOwnedByPlayer($planet_id)) {
            $this->user->planet_current = $planet_id;
            $this->user->save();
        }
    }

    /**
     * Get the amount of fleet slots that the player is currently using.
     *
     * This corresponds to the amount of fleet missions that are currently active for this player.
     *
     * @return int
     */
    public function getFleetSlotsInUse(): int
    {
        $fleetMissionService = resolve(FleetMissionService::class, ['player' => $this]);
        $activeMissions = $fleetMissionService->getActiveFleetMissionsSentByCurrentPlayer();

        return $activeMissions->count();
    }

    /**
     * Get the (maximum) amount of fleet slots that the player has available.
     *
     * This is calculated based on the player's research level and optional bonuses that may apply.
     *
     * @return int
     */
    public function getFleetSlotsMax(): int
    {
        // Calculate max fleet slots based on the user's computer research level.
        $object = ObjectService::getResearchObjectByMachineName('computer_technology');
        $fleet_slots_from_research = $object->performCalculation(CalculationType::MAX_FLEET_SLOTS, $this->getResearchLevel('computer_technology'));

        return $fleet_slots_from_research;
    }

    /**
     * Get the amount of expedition slots that the player is currently using.
     *
     * This corresponds to the amount of expedition missions that are currently active for this player.
     *
     * @return int
     */
    public function getExpeditionSlotsInUse(): int
    {
        $fleetMissionService = resolve(FleetMissionService::class, ['player' => $this]);
        $activeMissions = $fleetMissionService->getActiveFleetMissionsSentByCurrentPlayer();

        // Count only missions that are of type 15 (expedition)
        $expeditionMissions = $activeMissions->filter(function ($mission) {
            return $mission->mission_type === 15;
        });

        return $expeditionMissions->count();
    }

    /**
     * Get the (maximum) amount of expedition slots that the player has available.
     *
     * This is calculated based on the player's research level and optional bonuses that may apply.
     *
     * @return int
     */
    public function getExpeditionSlotsMax(): int
    {
        // Calculate max expedition slots based on the user's astrophysics research level.
        $object = ObjectService::getResearchObjectByMachineName('astrophysics');
        $expedition_slots_from_research = $object->performCalculation(CalculationType::MAX_EXPEDITION_SLOTS, $this->getResearchLevel('astrophysics'));

        // Add bonus expedition slots from settings (for timed events)
        $settingsService = app(SettingsService::class);
        $bonus_slots = $settingsService->bonusExpeditionSlots();

        return $expedition_slots_from_research + $bonus_slots;
    }

    /**
     * Update the player entity.
     *
     * This method is called every time the player logs in.
     * It updates the player's last IP and time properties.
     * It also updates the research queue.
     *
     * @return void
     * @throws Throwable
     */
    public function update(): void
    {
        DB::transaction(function () {
            // Attempt to acquire a lock on the row for this user. This is to prevent
            // race conditions when multiple requests are updating the same user and
            // potentially doing double insertions or overwriting each other's changes.
            $playerLock = User::where('id', $this->getId())
                ->lockForUpdate()
                ->first();

            if ($playerLock) {
                // ------
                // 1. Update research queue
                // ------
                $this->updateResearchQueue(false);

                // ------
                // 2. Update last_ip and time properties.
                // ------
                $this->user->time = (string)Carbon::now()->timestamp;
                $this->user->last_ip = request()->ip();

                $this->user->save();
            } else {
                throw new Exception('Could not acquire player update lock.');
            }
        });
    }

    /**
     * Update the research queue for this player.
     *
     * @param bool $save_user
     *   Optional flag whether to save the user in this method. This defaults to TRUE
     *   but can be set to FALSE when update happens in bulk and the caller method calls
     *   the save user itself to prevent on unnecessary multiple updates.
     *
     * @return void
     * @throws Exception
     */
    public function updateResearchQueue(bool $save_user = true): void
    {
        $queue = resolve(ResearchQueueService::class);
        $research_queue = $queue->retrieveFinishedForUser($this);

        // @TODO: add DB transaction wrapper
        foreach ($research_queue as $item) {
            // Get object information of research object.
            $object = ObjectService::getResearchObjectById($item->object_id);

            // Update planet and update level of the building that has been processed.
            $this->setResearchLevel($object->machine_name, $item->object_level_target);

            // Update build queue record
            $item->processed = 1;
            $item->save();

            // Build the next item in queue (if there is any)
            $queue->start($this, $item->time_end);
        }

        if ($save_user) {
            $this->user->save();
        }
    }

    /**
     * @throws Throwable
     */
    public function updateFleetMissions(): void
    {
        DB::transaction(function () {
            // Ensure planets are loaded before accessing
            if ($this->planets === null) {
                throw new RuntimeException('PlayerService planets not initialized for player ID: ' . $this->getId());
            }

            // Attempt to acquire a lock on the row for this planet. This is to prevent
            // race conditions when multiple requests are updating the fleet missions for the
            // same planet and potentially doing double insertions or overwriting each other's changes.
            $planetIds = $this->planets->allIds();
            $planetMissionUpdateLock = Planet::whereIn('id', $planetIds)
                ->lockForUpdate()
                ->get();

            if ($planetMissionUpdateLock->count() === count($planetIds)) {
                try {
                    $fleetMissionService = resolve(FleetMissionService::class, ['player' => $this]);
                    $missions = $fleetMissionService->getArrivedMissionsByPlanetIds($planetIds);

                    foreach ($missions as $mission) {
                        // Attempt to acquire a lock on the row for this fleet mission. This is to prevent
                        // race conditions when multiple requests are updating the same fleet mission and
                        // potentially doing double insertions or overwriting each other's changes.
                        $fleetMissionLock = FleetMission::where('id', $mission->id)
                            ->lockForUpdate()
                            ->first();

                        if ($fleetMissionLock) {
                            $fleetMissionService->updateMission($mission);
                        } else {
                            throw new Exception('Could not acquire update fleet mission update lock.');
                        }
                    }

                    if ($missions->count() > 0) {
                        // Update the current player object and all child planets to make sure any changes
                        // to the fleet missions are reflected in the player/planet objects.
                        $this->load($this->getId());
                    }
                } catch (Exception $e) {
                    \Log::error('Fleet mission processing error - full details', [
                        'player_id' => $this->getId(),
                        'error_message' => $e->getMessage(),
                        'error_file' => $e->getFile(),
                        'error_line' => $e->getLine(),
                        'error_trace' => $e->getTraceAsString(),
                    ]);
                    throw new RuntimeException('Fleet mission service process error: ' . $e->getMessage(), 0, $e);
                }
            } else {
                throw new Exception('Could not acquire update fleet mission planet lock.');
            }
        });
    }

    /**
     * Get the cached general score for this player from the database.
     *
     * @return int
     */
    public function getCachedGeneralScore(): int
    {
        if ($this->cachedGeneralScore === null) {
            $this->cachedGeneralScore = \OGame\Models\Highscore::where('player_id', $this->getId())->first()->general ?? 0;
        }
        return $this->cachedGeneralScore;
    }

    /**
     * Calculate and return planet score based on levels of buildings and amount of units.
     *
     * @return int
     */
    public function getResearchScore(): int
    {
        // For every research in the game, calculate the score based on how much resources it costs to build it.
        // For research it is the sum of resources needed for all levels up to the current level.
        // The score is the sum of all these values.
        $resources_spent = new Resources(0, 0, 0, 0);

        // Create object array
        $research_objects = ObjectService::getResearchObjects();
        foreach ($research_objects as $object) {
            for ($i = 1; $i <= $this->getResearchLevel($object->machine_name); $i++) {
                // Concatenate price which is array of metal, crystal and deuterium.
                $raw_price = ObjectService::getObjectRawPrice($object->machine_name, $i);
                $resources_spent->add($raw_price);
            }
        }

        // Divide the score by 1000 to get the amount of points. Floor the result.
        $resources_sum = $resources_spent->metal->get() + $resources_spent->crystal->get() + $resources_spent->deuterium->get();
        return (int)floor($resources_sum / 1000);
    }

    /**
     * Get array with all research objects that this player has.
     *
     * @return array<string, int>
     */
    public function getResearchArray(): array
    {
        $array = [];
        $objects = ObjectService::getResearchObjects();
        foreach ($objects as $object) {
            if ($this->user_tech->{$object->machine_name} > 0) {
                $array[$object->machine_name] = $this->user_tech->{$object->machine_name};
            }
        }

        return $array;
    }

    /**
     * Get is the player researching any tech or not
     *
     * @return bool
     */
    public function isResearching(): bool
    {
        $research_queue = resolve(ResearchQueueService::class);
        return (bool) $research_queue->activeResearchQueueItemCount($this);
    }

    public function isBuildingShipsOrDefense(): bool
    {
        $unit_queue = resolve(UnitQueueService::class);

        return $unit_queue->isBuildingShipsOrDefense($this->getCurrentPlanetId());
    }

    /**
     * Get is the player researching the tech or not
     *
     * @param string $machine_name
     * @param int $level
     * @return bool
     */
    public function isResearchingTech(string $machine_name, int $level): bool
    {
        $research_queue = resolve(ResearchQueueService::class);
        return $research_queue->objectInResearchQueue($this, $machine_name, $level);
    }

    /**
     * Get the maximum amount of planets that this player can have based on research levels.
     *
     * @return int
     */
    public function getMaxPlanetAmount(): int
    {
        $astrophyicsLevel = $this->getResearchLevel('astrophysics');
        $astrophysicsObject = ObjectService::getResearchObjectByMachineName('astrophysics');

        // +1 to max_colonies to get max_planets because the main planet is not included in the calculation above.
        return 1 + $astrophysicsObject->performCalculation(CalculationType::MAX_COLONIES, $astrophyicsLevel);
    }

    /**
     * Check if a planet position can be colonized based on astrophysics level.
     *
     * Certain positions require minimum astrophysics levels:
     * - Positions 1 and 15 require level 8
     * - Positions 2 and 14 require level 6
     * - Positions 3 and 13 require level 4
     * - Positions 4-12 have no special requirements
     *
     * @param int $position The planet position (1-15)
     * @return bool True if the position can be colonized, false otherwise
     */
    public function canColonizePosition(int $position): bool
    {
        $astrophysicsLevel = $this->getResearchLevel('astrophysics');

        // Check position-based requirements
        if (($position === 1 || $position === 15) && $astrophysicsLevel < 8) {
            return false;
        }

        if (($position === 2 || $position === 14) && $astrophysicsLevel < 6) {
            return false;
        }

        if (($position === 3 || $position === 13) && $astrophysicsLevel < 4) {
            return false;
        }

        return true;
    }

    /**
     * Get the missile range in systems based on Impulse Drive level.
     * Formula: (Impulse Drive Level - 1) × 5 + 5 systems
     *
     * @return int
     */
    public function getMissileRange(): int
    {
        $impulseDriveLevel = $this->getResearchLevel('impulse_drive');

        // If no Impulse Drive research, return 0
        if ($impulseDriveLevel === 0) {
            return 0;
        }

        // Calculate range: (level - 1) × 5 + 5
        return ($impulseDriveLevel - 1) * 5 + 5;
    }

    /**
     * Delete the player and all associated records from the database.
     *
     * @return void
     */
    public function delete(): void
    {
        // Loop through all planets and delete all records associated with them.
        foreach ($this->planets->all() as $planet) {
            // Delete all queue items.
            \OGame\Models\ResearchQueue::where('planet_id', $planet->getPlanetId())->delete();
            \OGame\Models\BuildingQueue::where('planet_id', $planet->getPlanetId())->delete();
            \OGame\Models\UnitQueue::where('planet_id', $planet->getPlanetId())->delete();
            // Delete all fleet missions.
            // Get all fleet missions for this planet then loop through them and delete them.
            // TODO: this might be a performance bottleneck if there are many missions. Consider using a bulk delete compatible
            // with the foreign key constraints instead.
            $missions = FleetMission::where('planet_id_from', $planet->getPlanetId())->orWhere('planet_id_to', $planet->getPlanetId())->get();
            foreach ($missions as $mission) {
                // Delete any that have this mission as their parent.
                \OGame\Models\FleetMission::where('parent_id', $mission->id)->delete();
                // Delete mission itself.
                $mission->delete();
            }
        }

        // Delete all messages.
        \OGame\Models\Message::where('user_id', $this->getId())->delete();

        // Delete all battle reports where this player is the defender (planet owner).
        // Note: We don't delete reports where they're the attacker to preserve history for defenders.
        \OGame\Models\BattleReport::where('planet_user_id', $this->getId())->delete();

        // Delete tech record.
        $this->user_tech->delete();

        // Delete all planets.
        \OGame\Models\Planet::where('user_id', $this->getId())->delete();

        // Delete the actual user.
        $this->user->delete();
    }

    /**
     * Get is the player building the object or not
     *
     * @return bool
     */
    public function isBuildingObject(string $machine_name): bool
    {
        foreach ($this->planets->all() as $planet) {
            if ($planet->isBuildingObject($machine_name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the player's current dark matter amount.
     *
     * @return float
     */
    public function getDarkMatter(): float
    {
        return $this->user->dark_matter ?? 0;
    }

    /**
     * Add dark matter to the player's account.
     *
     * @param float $amount
     * @return void
     */
    public function addDarkMatter(float $amount): void
    {
        $this->user->dark_matter = ($this->user->dark_matter ?? 0) + $amount;
        $this->user->save();
    }

    /**
     * Deduct dark matter from the player's account.
     *
     * @param float $amount
     * @return void
     * @throws Exception
     */
    public function deductDarkMatter(float $amount): void
    {
        if ($this->getDarkMatter() < $amount) {
            throw new Exception('Insufficient dark matter');
        }
        $this->user->dark_matter = $this->user->dark_matter - $amount;
        $this->user->save();
    }

    public function hasCommander(): bool
    {
        // TODO: add logic
        return false;
    }

    public function hasAdmiral(): bool
    {
        // TODO: add logic
        return false;
    }

    public function hasEngineer(): bool
    {
        // TODO: add logic
        return false;
    }

    public function hasGeologist(): bool
    {
        // TODO: add logic
        return false;
    }

    public function hasTechnocrat(): bool
    {
        // TODO: add logic
        return false;
    }

    public function hasCommandingStaff(): bool
    {
        return $this->hasCommander()
            && $this->hasAdmiral()
            && $this->hasEngineer()
            && $this->hasGeologist()
            && $this->hasTechnocrat();
    }

    /**
     * Check if the player has any moon with a sensor phalanx.
     *
     * @return bool
     */
    public function hasMoonWithPhalanx(): bool
    {
        foreach ($this->planets->allMoons() as $moon) {
            if ($moon->getObjectLevel('sensor_phalanx') > 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get the highest sensor phalanx level among all player's moons.
     *
     * @return int
     */
    public function getHighestPhalanxLevel(): int
    {
        $highestLevel = 0;
        foreach ($this->planets->allMoons() as $moon) {
            $level = $moon->getObjectLevel('sensor_phalanx');
            if ($level > $highestLevel) {
                $highestLevel = $level;
            }
        }
        return $highestLevel;
    }

    /**
     * Get the moon with the highest sensor phalanx level that can scan the target coordinates.
     *
     * @param int $targetGalaxy
     * @param int $targetSystem
     * @param int $targetPosition
     * @return PlanetService|null
     */
    public function getMoonWithPhalanxInRange(int $targetGalaxy, int $targetSystem, int $targetPosition): ?PlanetService
    {
        $bestMoon = null;
        $highestLevel = 0;

        foreach ($this->planets->allMoons() as $moon) {
            $phalanxLevel = $moon->getObjectLevel('sensor_phalanx');
            if ($phalanxLevel > 0) {
                // Calculate range
                $moonCoords = $moon->getPlanetCoordinates();
                $range = $this->calculatePhalanxRange($phalanxLevel);

                // Check if target is in range (must be same galaxy)
                if ($moonCoords->galaxy === $targetGalaxy) {
                    $systemDistance = abs($moonCoords->system - $targetSystem);
                    if ($systemDistance <= $range) {
                        // Use the moon with highest phalanx level in range
                        if ($phalanxLevel > $highestLevel) {
                            $bestMoon = $moon;
                            $highestLevel = $phalanxLevel;
                        }
                    }
                }
            }
        }

        return $bestMoon;
    }

    /**
     * Calculate the range of a sensor phalanx based on its level.
     * Formula: range = (level ^ 2) - 1
     *
     * @param int $level
     * @return int
     */
    public function calculatePhalanxRange(int $level): int
    {
        return ($level * $level) - 1;
    }

    /**
     * Calculate deuterium cost for a phalanx scan.
     *
     * @param int $fromGalaxy
     * @param int $fromSystem
     * @param int $toGalaxy
     * @param int $toSystem
     * @return int
     */
    public function calculatePhalanxCost(int $fromGalaxy, int $fromSystem, int $toGalaxy, int $toSystem): int
    {
        // Flat cost of 5000 deuterium per scan, regardless of distance
        return 5000;
    }
}
