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
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->string('icon');
            $table->integer('price')->comment('Price in Dark Matter');
            $table->string('rarity')->default('common')->comment('common, uncommon, rare');
            $table->string('type')->comment('special_offer, construction, research, shipyard, resource, etc.');
            $table->string('effect_type')->nullable()->comment('reduce_building_time, reduce_research_time, etc.');
            $table->integer('effect_value')->nullable()->comment('Effect value in seconds');
            $table->string('duration')->default('now')->comment('Duration: now for instant items');
            $table->string('category_id')->comment('Category hash for filtering');
            $table->string('ref_id')->unique()->comment('Unique reference ID for the item');
            $table->boolean('is_active')->default(true)->comment('Whether item is available in shop');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
