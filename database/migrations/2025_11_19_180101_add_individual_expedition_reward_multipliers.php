<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add new individual reward multiplier settings
        // These replace the old single expedition_rewards_multiplier
        DB::table('settings')->insert([
            [
                'key' => 'expedition_reward_multiplier_resources',
                'value' => '1.0',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'expedition_reward_multiplier_ships',
                'value' => '1.0',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'expedition_reward_multiplier_dark_matter',
                'value' => '1.0',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'expedition_reward_multiplier_items',
                'value' => '1.0',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Migrate old expedition_rewards_multiplier value to new resource multiplier
        $oldMultiplier = DB::table('settings')
            ->where('key', 'expedition_rewards_multiplier')
            ->value('value');

        if ($oldMultiplier !== null) {
            DB::table('settings')
                ->where('key', 'expedition_reward_multiplier_resources')
                ->update(['value' => $oldMultiplier]);

            // Keep the old setting for backward compatibility but mark it as deprecated
            DB::table('settings')
                ->where('key', 'expedition_rewards_multiplier')
                ->update(['value' => $oldMultiplier]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the new individual multiplier settings
        DB::table('settings')->whereIn('key', [
            'expedition_reward_multiplier_resources',
            'expedition_reward_multiplier_ships',
            'expedition_reward_multiplier_dark_matter',
            'expedition_reward_multiplier_items',
        ])->delete();
    }
};
