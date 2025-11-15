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
        // Insert bonus_expedition_slots setting with default value of 0
        DB::table('settings')->insert([
            'key' => 'bonus_expedition_slots',
            'value' => '0',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        // Remove the bonus_expedition_slots setting
        DB::table('settings')->where('key', 'bonus_expedition_slots')->delete();
    }
};
