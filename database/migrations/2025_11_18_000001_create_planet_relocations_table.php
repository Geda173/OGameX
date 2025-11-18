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
        Schema::create('planet_relocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('planet_id');
            $table->unsignedBigInteger('user_id');
            $table->integer('from_galaxy');
            $table->integer('from_system');
            $table->integer('from_position');
            $table->integer('to_galaxy');
            $table->integer('to_system');
            $table->integer('to_position');
            $table->integer('time_start'); // When relocation was initiated
            $table->integer('time_end'); // When relocation will execute (24h later)
            $table->boolean('processed')->default(false);
            $table->boolean('cancelled')->default(false);
            $table->string('cancel_reason')->nullable();
            $table->timestamps();

            $table->foreign('planet_id')->references('id')->on('planets')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('planet_relocations');
    }
};
