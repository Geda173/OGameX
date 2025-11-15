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
        // Change planet_user_id from unsigned to signed to allow NPC IDs (-1 for pirates, -2 for aliens)
        DB::statement('ALTER TABLE battle_reports MODIFY planet_user_id INT(10) NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to unsigned
        DB::statement('ALTER TABLE battle_reports MODIFY planet_user_id INT(10) UNSIGNED NULL');
    }
};
