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
            $table->integer('queue_position')->default(1)->after('planet_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('repair_queue', function (Blueprint $table) {
            $table->dropColumn('queue_position');
        });
    }
};
