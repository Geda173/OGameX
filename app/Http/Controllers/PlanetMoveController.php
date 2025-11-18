<?php

namespace OGame\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use OGame\Models\Enums\PlanetType;
use OGame\Models\Planet;
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
     * Relocate planet to new coordinates using Dark Matter
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

            // IMPORTANT: Planet can only move to the same position number in a different solar system
            if ($coordinates->position !== $position) {
                throw new Exception('Planet can only be relocated to position ' . $coordinates->position . ' in a different solar system.');
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

            // Calculate Dark Matter cost
            $dm_cost = 240000;

            // Check if player has enough Dark Matter
            if ($player->getDarkMatter() < $dm_cost) {
                throw new Exception('Insufficient Dark Matter. Requires ' . number_format($dm_cost) . ' DM.');
            }

            // Deduct Dark Matter
            $player->deductDarkMatter($dm_cost);

            // Find and move the moon if it exists at the same coordinates
            $moon = Planet::where([
                ['galaxy', $coordinates->galaxy],
                ['system', $coordinates->system],
                ['planet', $coordinates->position],
                ['planet_type', PlanetType::Moon->value],
                ['user_id', $player->getId()],
                ['destroyed', 0],
            ])->first();

            // Move the planet
            $planet->galaxy = $galaxy;
            $planet->system = $system;
            $planet->planet = $position;
            $planet->save();

            // Move the moon if it exists
            if ($moon) {
                $moon->galaxy = $galaxy;
                $moon->system = $system;
                $moon->planet = $position;
                $moon->save();
            }

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
