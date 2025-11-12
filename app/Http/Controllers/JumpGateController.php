<?php

namespace OGame\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use OGame\GameObjects\Models\Units\UnitCollection;
use OGame\Models\Planet;
use OGame\Services\ObjectService;
use OGame\Services\PlayerService;

class JumpGateController extends OGameController
{
    /**
     * Shows the jump gate index page
     *
     * @param Request $request
     * @param PlayerService $player
     * @return View
     * @throws Exception
     */
    public function index(Request $request, PlayerService $player): View
    {
        $this->setBodyId('jumpgate');
        $planet = $player->planets->current();

        // Verify this is a moon with a jump gate
        if (!$planet->isMoon()) {
            abort(404, 'Jump gate only available on moons');
        }

        $jumpGateLevel = $planet->getObjectLevel('jump_gate');
        if ($jumpGateLevel < 1) {
            abort(404, 'Jump gate not built');
        }

        // Get all player's moons with jump gates
        $availableMoons = [];
        foreach ($player->planets as $playerPlanet) {
            if ($playerPlanet->isMoon() &&
                $playerPlanet->getPlanetId() !== $planet->getPlanetId() &&
                $playerPlanet->getObjectLevel('jump_gate') >= 1) {

                $availableMoons[] = [
                    'id' => $playerPlanet->getPlanetId(),
                    'name' => $playerPlanet->getPlanetName(),
                    'coordinates' => $playerPlanet->getPlanetCoordinates(),
                    'cooldown_remaining' => $this->getCooldownRemaining($playerPlanet),
                ];
            }
        }

        // Get ships available on this moon
        $shipObjects = ObjectService::getShipObjects();
        $availableShips = [];
        foreach ($shipObjects as $shipObject) {
            $amount = $planet->getObjectAmount($shipObject->machine_name);
            if ($amount > 0) {
                $availableShips[] = [
                    'id' => $shipObject->id,
                    'machine_name' => $shipObject->machine_name,
                    'title' => $shipObject->title,
                    'amount' => $amount,
                ];
            }
        }

        // Check if current jump gate is on cooldown
        $currentCooldown = $this->getCooldownRemaining($planet);

        return view('ingame.jumpgate.index')->with([
            'player' => $player,
            'planet' => $planet,
            'available_moons' => $availableMoons,
            'available_ships' => $availableShips,
            'cooldown_remaining' => $currentCooldown,
        ]);
    }

    /**
     * Execute a jump gate jump
     *
     * @param Request $request
     * @param PlayerService $player
     * @return JsonResponse
     * @throws Exception
     */
    public function execute(Request $request, PlayerService $player): JsonResponse
    {
        $planet = $player->planets->current();

        // Verify this is a moon with a jump gate
        if (!$planet->isMoon() || $planet->getObjectLevel('jump_gate') < 1) {
            return response()->json([
                'success' => false,
                'message' => 'Jump gate not available',
            ]);
        }

        // Check cooldown
        $cooldownRemaining = $this->getCooldownRemaining($planet);
        if ($cooldownRemaining > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Jump gate is on cooldown for ' . gmdate('H:i:s', $cooldownRemaining),
            ]);
        }

        // Get target moon
        $targetMoonId = (int)$request->input('target_moon_id');
        $targetMoon = $player->planets->getPlanetById($targetMoonId);

        if (!$targetMoon || !$targetMoon->isMoon() || $targetMoon->getObjectLevel('jump_gate') < 1) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid target moon or no jump gate available',
            ]);
        }

        // Check target moon cooldown
        $targetCooldown = $this->getCooldownRemaining($targetMoon);
        if ($targetCooldown > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Target jump gate is on cooldown',
            ]);
        }

        // Get ships to transfer
        $units = $this->getUnitsFromRequest($request);

        // Verify planet has these ships
        if (!$planet->hasUnits($units)) {
            return response()->json([
                'success' => false,
                'message' => 'Not enough ships available',
            ]);
        }

        // Execute the jump
        $this->performJump($planet, $targetMoon, $units);

        return response()->json([
            'success' => true,
            'message' => 'Fleet successfully jumped to ' . $targetMoon->getPlanetName(),
        ]);
    }

    /**
     * Get cooldown remaining in seconds
     *
     * @param mixed $planet
     * @return int
     */
    private function getCooldownRemaining($planet): int
    {
        $model = Planet::find($planet->getPlanetId());
        if (!$model || !$model->jump_gate_cooldown) {
            return 0;
        }

        $cooldownEnd = $model->jump_gate_cooldown;
        $now = time();

        return max(0, $cooldownEnd - $now);
    }

    /**
     * Perform the actual jump
     *
     * @param mixed $sourcePlanet
     * @param mixed $targetPlanet
     * @param UnitCollection $units
     * @return void
     */
    private function performJump($sourcePlanet, $targetPlanet, UnitCollection $units): void
    {
        // Remove ships from source
        foreach ($units->units as $unitAmount) {
            $machineName = $unitAmount->unitObject->machine_name;
            $amount = $unitAmount->amount;
            $sourcePlanet->removeUnits($machineName, $amount);
        }

        // Add ships to target
        foreach ($units->units as $unitAmount) {
            $machineName = $unitAmount->unitObject->machine_name;
            $amount = $unitAmount->amount;
            $targetPlanet->addUnits($machineName, $amount);
        }

        // Set cooldown on both moons (1 hour = 3600 seconds)
        $cooldownEnd = time() + 3600;

        $sourceModel = Planet::find($sourcePlanet->getPlanetId());
        $sourceModel->jump_gate_cooldown = $cooldownEnd;
        $sourceModel->save();

        $targetModel = Planet::find($targetPlanet->getPlanetId());
        $targetModel->jump_gate_cooldown = $cooldownEnd;
        $targetModel->save();
    }

    /**
     * Get units from the request
     *
     * @param Request $request
     * @return UnitCollection
     * @throws Exception
     */
    private function getUnitsFromRequest(Request $request): UnitCollection
    {
        $units = new UnitCollection();
        foreach ($request->all() as $key => $value) {
            if (str_starts_with($key, 'ship_')) {
                $machineName = str_replace('ship_', '', $key);
                $amount = (int)$value;
                if ($amount > 0) {
                    $unitObject = ObjectService::getUnitObjectByMachineName($machineName);
                    $units->addUnit($unitObject, $amount);
                }
            }
        }

        return $units;
    }
}
