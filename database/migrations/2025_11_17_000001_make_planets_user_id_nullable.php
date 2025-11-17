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
            // Drop the foreign key constraint first
            $table->dropForeign(['user_id']);
        });

        Schema::table('planets', function (Blueprint $table) {
            // Make user_id nullable to support destroyed planets with no owner
            $table->integer('user_id', false, true)->nullable()->change();
        });

        Schema::table('planets', function (Blueprint $table) {
            // Re-add the foreign key constraint with nullable support
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
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
            // Drop the foreign key constraint
            $table->dropForeign(['user_id']);
        });

        Schema::table('planets', function (Blueprint $table) {
            // Revert to non-nullable
            $table->integer('user_id', false, true)->nullable(false)->change();
        });

        Schema::table('planets', function (Blueprint $table) {
            // Re-add the original foreign key constraint
            $table->foreign('user_id')->references('id')->on('users');
        });
    }
};
