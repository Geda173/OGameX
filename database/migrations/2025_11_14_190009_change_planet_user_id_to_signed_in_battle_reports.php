<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if column is already signed (migration already applied)
        $columnType = DB::select("
            SELECT COLUMN_TYPE
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'battle_reports'
            AND COLUMN_NAME = 'planet_user_id'
        ");

        // Only modify if column is unsigned
        if (!empty($columnType) && stripos($columnType[0]->COLUMN_TYPE, 'unsigned') !== false) {
            // Change planet_user_id from unsigned to signed to allow NPC IDs (-1 for pirates, -2 for aliens)
            DB::statement('ALTER TABLE battle_reports MODIFY planet_user_id INT(10) NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Check if column is signed (can be reverted)
        $columnType = DB::select("
            SELECT COLUMN_TYPE
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'battle_reports'
            AND COLUMN_NAME = 'planet_user_id'
        ");

        // Only revert if column is currently signed
        if (!empty($columnType) && stripos($columnType[0]->COLUMN_TYPE, 'unsigned') === false) {
            // Revert back to unsigned
            DB::statement('ALTER TABLE battle_reports MODIFY planet_user_id INT(10) UNSIGNED NULL');
        }
    }
};
