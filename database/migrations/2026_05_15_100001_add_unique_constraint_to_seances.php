<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * QA-03: Add unique constraint on seances(classe_id, date_session, time_block_id).
 *
 * This ensures that even under concurrent requests, two sessions for the same
 * (programme, date, time block) cannot be created. The find-or-create endpoint
 * in SessionController handles the unique violation gracefully via firstOrCreate
 * inside a DB transaction.
 */
return new class extends Migration
{
    public function up(): void
    {
        // First, remove any existing duplicate rows before adding the constraint
        // (keeps the one with the lowest id for each duplicate group)
        \DB::statement("
            DELETE FROM seances
            WHERE id NOT IN (
                SELECT MIN(id) FROM seances
                WHERE time_block_id IS NOT NULL
                GROUP BY classe_id, date_session, time_block_id
            )
            AND time_block_id IS NOT NULL
        ");

        Schema::table('seances', function (Blueprint $table) {
            // Only enforce uniqueness when time_block_id is set (partial unique constraint).
            // SQLite does not support partial indexes natively in Laravel's schema builder,
            // so we use a regular unique constraint here. Sessions without time_block_id
            // (legacy rows) are excluded by the nullable column allowing NULLs in unique indexes.
            $table->unique(
                ['classe_id', 'date_session', 'time_block_id'],
                'seances_unique_slot'
            );
        });
    }

    public function down(): void
    {
        Schema::table('seances', function (Blueprint $table) {
            $table->dropUnique('seances_unique_slot');
        });
    }
};
