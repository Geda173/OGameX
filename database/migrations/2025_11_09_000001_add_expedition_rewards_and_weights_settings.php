<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        // Insert expedition rewards multiplier setting (default 1.0 = no bonus)
        DB::table('settings')->updateOrInsert(
            ['key' => 'expedition_rewards_multiplier'],
            [
                'value' => '1.0',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Insert individual outcome weight settings with default values (0-100 scale, relative weights)
        $weights = [
            'expedition_weight_ships' => '24',           // 24% (relative)
            'expedition_weight_resources' => '34',       // 34% (relative)
            'expedition_weight_delay' => '9',            // 9% (relative)
            'expedition_weight_speedup' => '4',          // 4% (relative)
            'expedition_weight_nothing' => '29',         // 29% (relative)
            'expedition_weight_black_hole' => '1',       // 1% (relative)
            'expedition_weight_pirates' => '0',          // 0% (disabled by default - not yet implemented)
            'expedition_weight_aliens' => '0',           // 0% (disabled by default - not yet implemented)
            'expedition_weight_dark_matter' => '0',      // 0% (disabled by default - not yet implemented)
            'expedition_weight_merchant' => '0',         // 0% (disabled by default - not yet implemented)
        ];

        foreach ($weights as $key => $value) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                [
                    'value' => $value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        // Remove the expedition rewards multiplier setting
        DB::table('settings')->where('key', 'expedition_rewards_multiplier')->delete();

        // Remove all weight settings
        $weights = [
            'expedition_weight_ships',
            'expedition_weight_resources',
            'expedition_weight_delay',
            'expedition_weight_speedup',
            'expedition_weight_nothing',
            'expedition_weight_black_hole',
            'expedition_weight_pirates',
            'expedition_weight_aliens',
            'expedition_weight_dark_matter',
            'expedition_weight_merchant',
        ];

        foreach ($weights as $key) {
            DB::table('settings')->where('key', $key)->delete();
        }
    }
};
