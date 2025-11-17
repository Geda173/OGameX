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
        // Create repair_queue table only if it doesn't exist
        if (!Schema::hasTable('repair_queue')) {
            Schema::create('repair_queue', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('planet_id');
                $table->unsignedBigInteger('battle_report_id');
                $table->integer('ship_object_id'); // The object ID of the ship being repaired
                $table->integer('ship_amount'); // Number of ships being repaired
                $table->integer('metal_cost'); // Metal cost to repair
                $table->integer('crystal_cost'); // Crystal cost to repair
                $table->integer('deuterium_cost'); // Deuterium cost to repair
                $table->integer('time_duration'); // Repair duration in seconds
                $table->integer('time_start'); // Unix timestamp when repair started
                $table->integer('time_end'); // Unix timestamp when repair finishes
                $table->tinyInteger('processed')->default(0); // Whether the repair is complete
                $table->tinyInteger('canceled')->default(0); // Whether the repair was canceled
                $table->tinyInteger('claimed')->default(0); // Ships have been picked up by player
                $table->timestamps();

                // Add indexes for performance
                $table->index(['planet_id', 'processed']);
                $table->index(['time_end', 'processed']);
                $table->index(['battle_report_id']);

                // Add foreign keys
                $table->foreign('planet_id')->references('id')->on('planets')->onDelete('cascade');
                $table->foreign('battle_report_id')->references('id')->on('battle_reports')->onDelete('cascade');
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
        Schema::dropIfExists('repair_queue');
    }
};
