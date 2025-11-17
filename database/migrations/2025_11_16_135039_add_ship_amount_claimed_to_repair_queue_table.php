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
        Schema::table('repair_queue', function (Blueprint $table) {
            // Add ship_amount_claimed field to track partial collections
            $table->integer('ship_amount_claimed')->default(0)->after('ship_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('repair_queue', function (Blueprint $table) {
            $table->dropColumn('ship_amount_claimed');
        });
    }
};
