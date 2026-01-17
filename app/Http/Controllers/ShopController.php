<?php

namespace OGame\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use OGame\Models\Item;
use OGame\Models\UserItem;

class ShopController extends OGameController
{
    /**
     * Shows the shop index page
     *
     * @return View
     */
    public function index(): View
    {
        $this->setBodyId('shop');

        // Get all active items
        $items = Item::where('is_active', true)->get();

        // Get user's dark matter balance
        $darkMatter = auth()->user()->dark_matter ?? 0;

        return view('ingame.shop.index', compact('items', 'darkMatter'));
    }

    /**
     * Get item details for the overlay
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getItemDetails(Request $request): JsonResponse
    {
        try {
            $itemId = $request->input('item_id');
            if (empty($itemId)) {
                throw new Exception('No item ID provided.');
            }

            $item = Item::find($itemId);
            if (!$item) {
                throw new Exception('Item not found.');
            }

            $user = auth()->user();
            $darkMatter = $user->dark_matter ?? 0;
            // Check against discounted price if available
            $actualPrice = $item->getDiscountedPrice();
            $enoughDarkMatter = $darkMatter >= $actualPrice;

            // Get user's inventory count for this item
            $inventoryCount = UserItem::where('user_id', $user->id)
                ->where('item_id', $item->id)
                ->whereNull('used_at')
                ->sum('quantity');

            $view_html = view('ingame.ajax.shop.item-details')->with([
                'item' => $item,
                'darkMatter' => $darkMatter,
                'enoughDarkMatter' => $enoughDarkMatter,
                'inventoryCount' => $inventoryCount,
            ]);

            return response()->json([
                'target' => 'technologydetails',
                'content' => [
                    'technologydetails' => $view_html->render(),
                ],
                'files' => [
                    'js' => [],
                    'css' => [],
                ],
                'newAjaxToken' => csrf_token(),
                'page' => [
                    'stateObj' => [],
                    'title' => 'OGameX',
                    'url' => route('shop.index'),
                ],
                'serverTime' => time(),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Purchase an item
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function purchaseItem(Request $request): JsonResponse
    {
        try {
            $itemId = $request->input('item_id');
            if (empty($itemId)) {
                throw new Exception('No item ID provided.');
            }

            $item = Item::find($itemId);
            if (!$item) {
                throw new Exception('Item not found.');
            }

            $user = auth()->user();
            $darkMatter = $user->dark_matter ?? 0;

            // Use discounted price if available
            $actualPrice = $item->getDiscountedPrice();

            if ($darkMatter < $actualPrice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not enough Dark Matter.',
                ], 400);
            }

            // Deduct dark matter
            $user->dark_matter -= $actualPrice;
            $user->save();

            // Add item to user's inventory
            UserItem::create([
                'user_id' => $user->id,
                'item_id' => $item->id,
                'quantity' => 1,
                'obtained_at' => now(),
            ]);

            // TODO: Create dark matter transaction record
            // DarkMatterTransaction::create([...]);

            return response()->json([
                'success' => true,
                'message' => 'Item purchased successfully!',
                'darkMatter' => $user->dark_matter,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get user's inventory
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getInventory(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();

            // Get all unused items in user's inventory
            $inventoryItems = UserItem::where('user_id', $user->id)
                ->whereNull('used_at')
                ->with('item')
                ->get()
                ->groupBy('item_id')
                ->map(function ($items) {
                    return [
                        'item' => $items->first()->item,
                        'quantity' => $items->sum('quantity'),
                    ];
                });

            $view_html = view('ingame.ajax.shop.inventory')->with([
                'inventoryItems' => $inventoryItems,
            ]);

            return response()->json([
                'success' => true,
                'html' => $view_html->render(),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
