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
        Schema::table('users', function (Blueprint $table) {
            // Add outlaw_until timestamp column to track when the outlaw status expires
            // When a player under noob protection attacks a strong player, they become "outlaw" for 7 days
            // The column stores the timestamp when the outlaw status expires (null = not outlaw)
            $table->timestamp('outlaw_until')->nullable()->after('username_updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('outlaw_until');
        });
    }
};
