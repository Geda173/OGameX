<?php

namespace OGame\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
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

            // Check if target position is free
            $existing_planet = Planet::where([
                ['galaxy', $galaxy],
                ['system', $system],
                ['planet', $position],
                ['destroyed', 0],
            ])->first();

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

            // Move the planet
            $planet->galaxy = $galaxy;
            $planet->system = $system;
            $planet->planet = $position;
            $planet->save();

            return response()->json([
                'success' => true,
                'message' => 'Planet successfully relocated to ' . $galaxy . ':' . $system . ':' . $position,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
