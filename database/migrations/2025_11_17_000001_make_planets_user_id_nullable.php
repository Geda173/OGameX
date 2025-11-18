<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        // Get the actual foreign key constraint name from the database
        $constraintName = $this->getForeignKeyName('planets', 'user_id');

        if ($constraintName) {
            // Drop the foreign key constraint using raw SQL
            DB::statement("ALTER TABLE planets DROP FOREIGN KEY `{$constraintName}`");
        }

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
        // Get the current foreign key constraint name
        $constraintName = $this->getForeignKeyName('planets', 'user_id');

        if ($constraintName) {
            // Drop the foreign key constraint using raw SQL
            DB::statement("ALTER TABLE planets DROP FOREIGN KEY `{$constraintName}`");
        }

        Schema::table('planets', function (Blueprint $table) {
            // Revert to non-nullable
            $table->integer('user_id', false, true)->nullable(false)->change();
        });

        Schema::table('planets', function (Blueprint $table) {
            // Re-add the original foreign key constraint
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Get the foreign key constraint name for a given column.
     *
     * @param string $table
     * @param string $column
     * @return string|null
     */
    private function getForeignKeyName(string $table, string $column): ?string
    {
        $databaseName = DB::getDatabaseName();

        $result = DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = ?
            AND TABLE_NAME = ?
            AND COLUMN_NAME = ?
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ", [$databaseName, $table, $column]);

        return $result[0]->CONSTRAINT_NAME ?? null;
    }
};
