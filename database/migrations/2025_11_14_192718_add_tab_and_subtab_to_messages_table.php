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
        // Check if columns already exist before adding them
        Schema::table('messages', function (Blueprint $table) {
            if (!Schema::hasColumn('messages', 'tab')) {
                $table->string('tab')->nullable()->after('key');
            }
            if (!Schema::hasColumn('messages', 'subtab')) {
                $table->string('subtab')->nullable()->after('tab');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Check if columns exist before dropping them
        Schema::table('messages', function (Blueprint $table) {
            $columnsToDrops = [];
            if (Schema::hasColumn('messages', 'tab')) {
                $columnsToDrops[] = 'tab';
            }
            if (Schema::hasColumn('messages', 'subtab')) {
                $columnsToDrops[] = 'subtab';
            }
            if (!empty($columnsToDrops)) {
                $table->dropColumn($columnsToDrops);
            }
        });
    }
};
