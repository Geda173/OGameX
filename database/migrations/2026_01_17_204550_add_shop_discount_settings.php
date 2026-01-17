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
        $settings = [
            ['key' => 'shop_discount_enabled', 'value' => '0'], // Disabled by default
            ['key' => 'shop_discount_percentage', 'value' => '0'], // 0-100 percentage
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->insertOrIgnore($setting);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $keys = [
            'shop_discount_enabled',
            'shop_discount_percentage',
        ];

        DB::table('settings')->whereIn('key', $keys)->delete();
    }
};
