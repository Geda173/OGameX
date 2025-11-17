<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if the column already exists to prevent errors on re-run
        if (!Schema::hasColumn('users', 'espionage_probes_amount')) {
            Schema::table('users', function (Blueprint $table) {
                $table->tinyInteger('espionage_probes_amount')->default(3)->unsigned();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'espionage_probes_amount')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('espionage_probes_amount');
            });
        }
    }
};
