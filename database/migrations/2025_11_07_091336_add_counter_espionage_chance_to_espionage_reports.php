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
        if (!Schema::hasColumn('espionage_reports', 'counter_espionage_chance')) {
            Schema::table('espionage_reports', function (Blueprint $table) {
                $table->tinyInteger('counter_espionage_chance')->default(0)->after('player_info');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Check if column exists before dropping it
        if (Schema::hasColumn('espionage_reports', 'counter_espionage_chance')) {
            Schema::table('espionage_reports', function (Blueprint $table) {
                $table->dropColumn('counter_espionage_chance');
            });
        }
    }
};
