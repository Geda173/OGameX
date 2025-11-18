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
        // For SQLite (testing), just try to create the index
        // For MySQL/MariaDB, check if it exists first
        if (DB::connection()->getDriverName() === 'sqlite') {
            try {
                Schema::table('acs_groups', function (Blueprint $table) {
                    $table->index(['galaxy_to', 'system_to', 'position_to', 'type_to', 'status'], 'idx_target_coords_status');
                });
            } catch (\Exception $e) {
                // Index might already exist, ignore
            }
        } else {
            // Check if the index already exists before attempting to create it (MySQL/MariaDB)
            $indexExists = DB::select("
                SELECT COUNT(*) as count
                FROM information_schema.statistics
                WHERE table_schema = DATABASE()
                AND table_name = 'acs_groups'
                AND index_name = 'idx_target_coords_status'
            ");

            if ($indexExists[0]->count == 0) {
                Schema::table('acs_groups', function (Blueprint $table) {
                    // Add composite index for finding available ACS groups by target coordinates
                    // This significantly speeds up getGroupsForTarget() queries
                    $table->index(['galaxy_to', 'system_to', 'position_to', 'type_to', 'status'], 'idx_target_coords_status');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // For SQLite (testing), just try to drop the index
        // For MySQL/MariaDB, check if it exists first
        if (DB::connection()->getDriverName() === 'sqlite') {
            try {
                Schema::table('acs_groups', function (Blueprint $table) {
                    $table->dropIndex('idx_target_coords_status');
                });
            } catch (\Exception $e) {
                // Index might not exist, ignore
            }
        } else {
            // Check if the index exists before attempting to drop it (MySQL/MariaDB)
            $indexExists = DB::select("
                SELECT COUNT(*) as count
                FROM information_schema.statistics
                WHERE table_schema = DATABASE()
                AND table_name = 'acs_groups'
                AND index_name = 'idx_target_coords_status'
            ");

            if ($indexExists[0]->count > 0) {
                Schema::table('acs_groups', function (Blueprint $table) {
                    // Drop the composite index
                    $table->dropIndex('idx_target_coords_status');
                });
            }
        }
    }
};
