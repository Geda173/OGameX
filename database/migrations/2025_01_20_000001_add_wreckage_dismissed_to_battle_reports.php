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
        // Add wreckage_dismissed column if it doesn't exist
        if (Schema::hasTable('battle_reports') && !Schema::hasColumn('battle_reports', 'wreckage_dismissed')) {
            Schema::table('battle_reports', function (Blueprint $table) {
                $table->tinyInteger('wreckage_dismissed')->default(0)->after('wreckage');
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
        if (Schema::hasColumn('battle_reports', 'wreckage_dismissed')) {
            Schema::table('battle_reports', function (Blueprint $table) {
                $table->dropColumn('wreckage_dismissed');
            });
        }
    }
};
