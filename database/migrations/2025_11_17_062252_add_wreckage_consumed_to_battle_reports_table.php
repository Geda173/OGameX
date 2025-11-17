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
        Schema::table('battle_reports', function (Blueprint $table) {
            // Add wreckage_consumed field to track how much wreckage has been used for repairs
            // This allows wreckage field to store raw defender losses
            // Available wreckage = (raw * recovery%) - consumed
            $table->json('wreckage_consumed')->nullable()->after('wreckage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('battle_reports', function (Blueprint $table) {
            $table->dropColumn('wreckage_consumed');
        });
    }
};
