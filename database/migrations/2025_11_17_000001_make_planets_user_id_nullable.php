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
        // For SQLite (testing), handle differently
        if (DB::connection()->getDriverName() === 'sqlite') {
            // SQLite doesn't support dropping/modifying columns easily, so we skip this
            // The column will be created as nullable in the original migration
            return;
        }

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
        // For SQLite (testing), handle differently
        if (DB::connection()->getDriverName() === 'sqlite') {
            // SQLite doesn't support this easily, so skip
            return;
        }

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
        // For SQLite, we can't query information_schema, so just return a generic name
        if (DB::connection()->getDriverName() === 'sqlite') {
            // SQLite foreign keys are handled differently - we'll try to drop by column
            return null;
        }

        // For MySQL/MariaDB
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
