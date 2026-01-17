<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use OGame\Models\Item;

class ItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            // KRAKEN Gold
            [
                'name' => 'KRAKEN Gold',
                'description' => 'Reduces the building time of buildings currently under construction by <b>6h</b>.<br /><br />Duration: now<br /><br />Price: 7.000 Dark Matter<br />In Inventory: 0',
                'icon' => 'kraken_gold.png',
                'price' => 7000,
                'rarity' => 'rare',
                'type' => 'special_offer',
                'effect_type' => 'reduce_building_time',
                'effect_value' => 21600, // 6 hours in seconds
                'duration' => 'now',
                'category_id' => 'c18170d3125b9941ef3a86bd28dded7bf2066a6a', // Special offers
                'ref_id' => '929d5e15709cc51a4500de4499e19763c879f7f7',
                'is_active' => true,
            ],
            // DETROID Gold
            [
                'name' => 'DETROID Gold',
                'description' => 'Reduces the construction time of current shipyard-contracts by <b>6h</b>.<br /><br />Duration: now<br /><br />Price: 7.000 Dark Matter<br />In Inventory: 0',
                'icon' => 'detroid_gold.png',
                'price' => 7000,
                'rarity' => 'rare',
                'type' => 'special_offer',
                'effect_type' => 'reduce_shipyard_time',
                'effect_value' => 21600, // 6 hours in seconds
                'duration' => 'now',
                'category_id' => 'c18170d3125b9941ef3a86bd28dded7bf2066a6a', // Special offers
                'ref_id' => '0968999df2fe956aa4a07aea74921f860af7d97f',
                'is_active' => true,
            ],
            // NEWTRON Gold
            [
                'name' => 'NEWTRON Gold',
                'description' => 'Reduces research time for all research that is currently in progress by <b>6h</b>.<br /><br />Duration: now<br /><br />Price: 7.000 Dark Matter<br />In Inventory: 0',
                'icon' => 'newtron_gold.png',
                'price' => 7000,
                'rarity' => 'rare',
                'type' => 'special_offer',
                'effect_type' => 'reduce_research_time',
                'effect_value' => 21600, // 6 hours in seconds
                'duration' => 'now',
                'category_id' => 'c18170d3125b9941ef3a86bd28dded7bf2066a6a', // Special offers
                'ref_id' => '8a4f9e8309e1078f7f5ced47d558d30ae15b4a1b',
                'is_active' => true,
            ],
            // KRAKEN Silver
            [
                'name' => 'KRAKEN Silver',
                'description' => 'Reduces the building time of buildings currently under construction by <b>2h</b>.<br /><br />Duration: now<br /><br />Price: 2.500 Dark Matter<br />In Inventory: 0',
                'icon' => 'kraken_silver.png',
                'price' => 2500,
                'rarity' => 'uncommon',
                'type' => 'special_offer',
                'effect_type' => 'reduce_building_time',
                'effect_value' => 7200, // 2 hours in seconds
                'duration' => 'now',
                'category_id' => 'c18170d3125b9941ef3a86bd28dded7bf2066a6a', // Special offers
                'ref_id' => '4a58d4978bbe24e3efb3b0248e21b3b4b1bfbd8a',
                'is_active' => true,
            ],
            // DETROID Silver
            [
                'name' => 'DETROID Silver',
                'description' => 'Reduces the construction time of current shipyard-contracts by <b>2h</b>.<br /><br />Duration: now<br /><br />Price: 2.500 Dark Matter<br />In Inventory: 0',
                'icon' => 'detroid_silver.png',
                'price' => 2500,
                'rarity' => 'uncommon',
                'type' => 'special_offer',
                'effect_type' => 'reduce_shipyard_time',
                'effect_value' => 7200, // 2 hours in seconds
                'duration' => 'now',
                'category_id' => 'c18170d3125b9941ef3a86bd28dded7bf2066a6a', // Special offers
                'ref_id' => '27cbcd52f16693023cb966e5026d8a1efbbfc0f9',
                'is_active' => true,
            ],
            // NEWTRON Silver
            [
                'name' => 'NEWTRON Silver',
                'description' => 'Reduces research time for all research that is currently in progress by <b>2h</b>.<br /><br />Duration: now<br /><br />Price: 2.500 Dark Matter<br />In Inventory: 0',
                'icon' => 'newtron_silver.png',
                'price' => 2500,
                'rarity' => 'uncommon',
                'type' => 'special_offer',
                'effect_type' => 'reduce_research_time',
                'effect_value' => 7200, // 2 hours in seconds
                'duration' => 'now',
                'category_id' => 'c18170d3125b9941ef3a86bd28dded7bf2066a6a', // Special offers
                'ref_id' => 'd26f4dab76fdc5296e3ebec11a1e1d2558c713ea',
                'is_active' => true,
            ],
            // KRAKEN Bronze
            [
                'name' => 'KRAKEN Bronze',
                'description' => 'Reduces the building time of buildings currently under construction by <b>30m</b>.<br /><br />Duration: now<br /><br />Price: 700 Dark Matter<br />In Inventory: 0',
                'icon' => 'kraken_bronze.png',
                'price' => 700,
                'rarity' => 'common',
                'type' => 'special_offer',
                'effect_type' => 'reduce_building_time',
                'effect_value' => 1800, // 30 minutes in seconds
                'duration' => 'now',
                'category_id' => 'c18170d3125b9941ef3a86bd28dded7bf2066a6a', // Special offers
                'ref_id' => '40f6c78e11be01ad3389b7dccd6ab8efa9347f3c',
                'is_active' => true,
            ],
            // DETROID Bronze
            [
                'name' => 'DETROID Bronze',
                'description' => 'Reduces the construction time of current shipyard-contracts by <b>30m</b>.<br /><br />Duration: now<br /><br />Price: 700 Dark Matter<br />In Inventory: 0',
                'icon' => 'detroid_bronze.png',
                'price' => 700,
                'rarity' => 'common',
                'type' => 'special_offer',
                'effect_type' => 'reduce_shipyard_time',
                'effect_value' => 1800, // 30 minutes in seconds
                'duration' => 'now',
                'category_id' => 'c18170d3125b9941ef3a86bd28dded7bf2066a6a', // Special offers
                'ref_id' => 'd3d541ecc23e4daa0c698e44c32f04afd2037d84',
                'is_active' => true,
            ],
            // NEWTRON Bronze
            [
                'name' => 'NEWTRON Bronze',
                'description' => 'Reduces research time for all research that is currently in progress by <b>30m</b>.<br /><br />Duration: now<br /><br />Price: 700 Dark Matter<br />In Inventory: 0',
                'icon' => 'newtron_bronze.png',
                'price' => 700,
                'rarity' => 'common',
                'type' => 'special_offer',
                'effect_type' => 'reduce_research_time',
                'effect_value' => 1800, // 30 minutes in seconds
                'duration' => 'now',
                'category_id' => 'c18170d3125b9941ef3a86bd28dded7bf2066a6a', // Special offers
                'ref_id' => 'da4a2a1bb9afd410be07bc9736d87f1c8059e66d',
                'is_active' => true,
            ],
        ];

        foreach ($items as $itemData) {
            Item::updateOrCreate(
                ['ref_id' => $itemData['ref_id']],
                $itemData
            );
        }
    }
}
