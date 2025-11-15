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
        Schema::table('building_queues', function (Blueprint $table) {
            // Add teardown flag to indicate if this is a teardown operation
            $table->tinyInteger('teardown')->default(0)->after('building');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('building_queues', function (Blueprint $table) {
            $table->dropColumn('teardown');
        });
    }
};
