<?php

namespace OGame\Http\Controllers;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use OGame\Models\Enums\PlanetType;
use OGame\Models\Planet;
use OGame\Models\PlanetRelocation;
use OGame\Services\PlayerService;

class PlanetMoveController extends OGameController
{
    /**
     * Shows the planet move page
     *
     * @param PlayerService $player
     * @return View
     */
    public function index(PlayerService $player): View
    {
        $planet = $player->planets->current();
        $coordinates = $planet->getPlanetCoordinates();

        return view('ingame.planetmove.index')->with([
            'planet' => $planet,
            'current_galaxy' => $coordinates->galaxy,
            'current_system' => $coordinates->system,
            'current_position' => $coordinates->position,
            'dark_matter' => $player->getDarkMatter(),
            'relocation_cost' => 240000,
        ]);
    }

    /**
     * Initiate planet relocation (starts 24-hour countdown)
     *
     * @param Request $request
     * @param PlayerService $player
     * @return JsonResponse
     */
    public function relocate(Request $request, PlayerService $player): JsonResponse
    {
        try {
            $galaxy = (int)$request->input('galaxy');
            $system = (int)$request->input('system');
            $position = (int)$request->input('position');

            $planetService = $player->planets->current();
            $coordinates = $planetService->getPlanetCoordinates();

            // Get the actual Planet model
            $planet = Planet::find($planetService->getPlanetId());
            if (!$planet) {
                throw new Exception('Planet not found.');
            }

            // Validate coordinates
            if ($galaxy < 1 || $galaxy > 9) {
                throw new Exception('Invalid galaxy. Must be between 1 and 9.');
            }
            if ($system < 1 || $system > 499) {
                throw new Exception('Invalid system. Must be between 1 and 499.');
            }
            if ($position < 1 || $position > 15) {
                throw new Exception('Invalid position. Must be between 1 and 15.');
            }

            // Check if same location
            if ($coordinates->galaxy === $galaxy && $coordinates->system === $system && $coordinates->position === $position) {
                throw new Exception('Planet is already at this location.');
            }

            // IMPORTANT: Position range restrictions (1-3, 4-12, 13-15)
            $currentPos = $coordinates->position;
            $targetPos = $position;

            $positionRanges = [
                [1, 3],
                [4, 12],
                [13, 15]
            ];

            $currentRange = null;
            $targetRange = null;

            foreach ($positionRanges as $range) {
                if ($currentPos >= $range[0] && $currentPos <= $range[1]) {
                    $currentRange = $range;
                }
                if ($targetPos >= $range[0] && $targetPos <= $range[1]) {
                    $targetRange = $range;
                }
            }

            if ($currentRange !== $targetRange) {
                if ($currentRange[0] == 1) {
                    throw new Exception('Planets at positions 1-3 can only move to positions 1-3.');
                } elseif ($currentRange[0] == 4) {
                    throw new Exception('Planets at positions 4-12 can only move to positions 4-12.');
                } else {
                    throw new Exception('Planets at positions 13-15 can only move to positions 13-15.');
                }
            }

            // Check if target position is free (excluding the current planet and checking only for planets, not moons)
            $existing_planet = Planet::where([
                ['galaxy', $galaxy],
                ['system', $system],
                ['planet', $position],
                ['planet_type', PlanetType::Planet->value],
                ['destroyed', 0],
            ])
            ->where('id', '!=', $planet->id)
            ->first();

            if ($existing_planet) {
                throw new Exception('Target position is already occupied.');
            }

            // Check if player has enough Dark Matter (don't charge yet, just verify)
            $dm_cost = 240000;
            if ($player->getDarkMatter() < $dm_cost) {
                throw new Exception('Insufficient Dark Matter. Requires ' . number_format($dm_cost) . ' DM.');
            }

            // Check for 24-hour cooldown (any relocation attempt in last 24h)
            $lastRelocation = PlanetRelocation::where('planet_id', $planet->id)
                ->where('time_start', '>', Carbon::now()->subHours(24)->timestamp)
                ->first();

            if ($lastRelocation) {
                $timeRemaining = $lastRelocation->time_start + (24 * 3600) - Carbon::now()->timestamp;
                $hours = floor($timeRemaining / 3600);
                $minutes = floor(($timeRemaining % 3600) / 60);
                throw new Exception("You can only attempt to relocate a planet once per 24 hours. Time remaining: {$hours}h {$minutes}m");
            }

            // Check if planet already has a pending relocation
            $pendingRelocation = PlanetRelocation::where('planet_id', $planet->id)
                ->where('processed', false)
                ->where('cancelled', false)
                ->first();

            if ($pendingRelocation) {
                throw new Exception('This planet already has a pending relocation.');
            }

            // Create relocation record (24-hour countdown)
            $currentTime = Carbon::now()->timestamp;
            $relocationTime = $currentTime + (24 * 3600); // 24 hours from now

            PlanetRelocation::create([
                'planet_id' => $planet->id,
                'user_id' => $player->getId(),
                'from_galaxy' => $coordinates->galaxy,
                'from_system' => $coordinates->system,
                'from_position' => $coordinates->position,
                'to_galaxy' => $galaxy,
                'to_system' => $system,
                'to_position' => $position,
                'time_start' => $currentTime,
                'time_end' => $relocationTime,
                'processed' => false,
                'cancelled' => false,
            ]);

            return response()->json([
                'error' => '', // Empty string indicates success
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ]);
        }
    }
}
