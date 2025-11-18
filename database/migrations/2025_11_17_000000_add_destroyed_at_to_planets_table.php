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
            // Add timestamp for when the planet was destroyed/abandoned
            // This will be used to determine when the planet can be recolonized
            $table->integer('destroyed_at')->nullable()->after('destroyed');
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
            $table->dropColumn('destroyed_at');
        });
    }
};
