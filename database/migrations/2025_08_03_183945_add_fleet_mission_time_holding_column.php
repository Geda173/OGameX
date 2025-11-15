<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if column already exists before adding it
        if (!Schema::hasColumn('fleet_missions', 'time_holding')) {
            Schema::table('fleet_missions', function (Blueprint $table) {
                $table->integer('time_holding')->nullable()->after('time_arrival');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Check if column exists before dropping it
        if (Schema::hasColumn('fleet_missions', 'time_holding')) {
            Schema::table('fleet_missions', function (Blueprint $table) {
                $table->dropColumn('time_holding');
            });
        }
    }
};
