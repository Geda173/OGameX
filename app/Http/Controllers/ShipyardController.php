<?php

namespace OGame\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use OGame\Http\Controllers\Abstracts\AbstractUnitsController;
use OGame\Services\PlayerService;
use OGame\Services\UnitQueueService;

class ShipyardController extends AbstractUnitsController
{
    /**
     * ShipyardController constructor.
     *
     * @param UnitQueueService $queue
     */
    public function __construct(UnitQueueService $queue)
    {
        $this->route_view_index = 'shipyard.index';
        parent::__construct($queue);
    }

    /**
     * Shows the shipyard index page
     *
     * @param Request $request
     * @param PlayerService $player
     * @return View
     * @throws Exception
     */
    public function index(Request $request, PlayerService $player): View
    {
        $this->setBodyId('shipyard');

        // Prepare custom properties
        $this->objects = [
            0 => ['light_fighter', 'heavy_fighter', 'cruiser', 'battle_ship', 'battlecruiser', 'bomber', 'destroyer', 'deathstar'],
            1 => ['small_cargo', 'large_cargo', 'colony_ship', 'recycler', 'espionage_probe', 'solar_satellite'],
        ];

        return view(view: 'ingame.shipyard.index')->with(
            array_merge(
                [
                    'shipyard_upgrading' => $player->planets->current()->isBuildingObject('shipyard'),
                    'nanite_factory_upgrading' => $player->planets->current()->isBuildingObject('nano_factory'),
                ],
                parent::indexPage($request, $player)
            )
        );
    }

    /**
     * Handles the shipyard page AJAX requests.
     *
     * @param Request $request
     * @param PlayerService $player
     * @return JsonResponse
     * @throws Exception
     */
    public function ajax(Request $request, PlayerService $player): JsonResponse
    {
        return $this->ajaxHandler($request, $player);
    }

    /**
     * @param Request $request
     * @param PlayerService $player
     * @return JsonResponse
     * @throws Exception
     */
    public function addBuildRequest(Request $request, PlayerService $player): JsonResponse
    {
        // If the shipyard is upgrading, it shouldn't be allowed.
        if ($player->planets->current()->isBuildingObject('shipyard')) {
            return response()->json([
                'success' => false,
                'errors' => [['message' => __('Shipyard is being upgraded.')]],
            ]);
        }
        // If the nanite factory is upgrading, it shouldn't be allowed.
        if ($player->planets->current()->isBuildingObject('nano_factory')) {
            return response()->json([
                'success' => false,
                'errors' => [['message' => __('Nanite Factory is being upgraded.')]],
            ]);
        }
        // If neither are upgrading, we can continue to process the request.
        return parent::addBuildRequest($request, $player);
    }
}
