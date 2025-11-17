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
        // Check if the index already exists before attempting to create it
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Check if the index exists before attempting to drop it
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
};
