<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        // Check if column already exists before adding it
        if (!Schema::hasColumn('building_queues', 'teardown')) {
            Schema::table('building_queues', function (Blueprint $table) {
                // Add teardown flag to indicate if this is a teardown operation
                $table->tinyInteger('teardown')->default(0)->after('building');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        // Check if column exists before dropping it
        if (Schema::hasColumn('building_queues', 'teardown')) {
            Schema::table('building_queues', function (Blueprint $table) {
                $table->dropColumn('teardown');
            });
        }
    }
};
