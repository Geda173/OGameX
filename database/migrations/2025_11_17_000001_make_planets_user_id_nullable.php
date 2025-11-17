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
        Schema::table('planets', function (Blueprint $table) {
            // Make user_id nullable to support destroyed planets with no owner
            $table->integer('user_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('planets', function (Blueprint $table) {
            // Revert to non-nullable
            $table->integer('user_id')->nullable(false)->change();
        });
    }
};
