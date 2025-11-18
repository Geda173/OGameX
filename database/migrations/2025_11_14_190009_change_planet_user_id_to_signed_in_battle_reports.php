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
        // For SQLite (testing), just try to apply the migration
        // SQLite doesn't have unsigned integers, so this is a no-op for tests
        if (DB::connection()->getDriverName() === 'sqlite') {
            try {
                Schema::table('battle_reports', function (Blueprint $table) {
                    // Try to drop foreign key if it exists
                    $table->dropForeign(['planet_user_id']);
                });
            } catch (\Exception $e) {
                // Foreign key might not exist, ignore
            }
            return;
        }

        // For MySQL/MariaDB, check if column is already signed (migration already applied)
        $columnType = DB::select("
            SELECT COLUMN_TYPE
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'battle_reports'
            AND COLUMN_NAME = 'planet_user_id'
        ");

        // Only modify if column is unsigned
        if (!empty($columnType) && stripos($columnType[0]->COLUMN_TYPE, 'unsigned') !== false) {
            Schema::table('battle_reports', function (Blueprint $table) {
                // Drop foreign key constraint - we cannot maintain this constraint
                // because we now allow negative IDs for NPCs (-1 for pirates, -2 for aliens)
                // which don't exist in the users table
                $table->dropForeign(['planet_user_id']);
            });

            // Change planet_user_id from unsigned to signed to allow NPC IDs (-1 for pirates, -2 for aliens)
            DB::statement('ALTER TABLE battle_reports MODIFY planet_user_id INT(10) NULL');

            // Note: We do NOT re-add the foreign key constraint because:
            // 1. NPC IDs (negative values) won't exist in the users table
            // 2. Application code handles the relationship appropriately
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // For SQLite (testing), just try to restore the foreign key
        if (DB::connection()->getDriverName() === 'sqlite') {
            try {
                Schema::table('battle_reports', function (Blueprint $table) {
                    $table->foreign('planet_user_id')->references('id')->on('users')->onDelete('cascade');
                });
            } catch (\Exception $e) {
                // Might fail, ignore
            }
            return;
        }

        // For MySQL/MariaDB, check if column is signed (can be reverted)
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

            Schema::table('battle_reports', function (Blueprint $table) {
                // Re-add foreign key constraint (since we're back to unsigned)
                $table->foreign('planet_user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }
};
