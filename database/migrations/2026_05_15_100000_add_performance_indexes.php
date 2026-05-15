<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PERF-04: Add database indexes to frequently queried columns.
 *
 * attendances:
 *   - status        → used in scope filters (unjustified list, stats)
 *   - created_by_user_id → used in audit queries
 *   - (session_id, stagiaire_id) composite → covers the unique lookup in bulk operations
 *
 * seances:
 *   - (classe_id, date_session, time_block_id) composite → covers find-or-create lookups
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->index('status', 'attendances_status_idx');
            $table->index('created_by_user_id', 'attendances_created_by_user_id_idx');
            // Covers the (session_id, stagiaire_id) unique constraint lookups without a full scan
            $table->index(['session_id', 'stagiaire_id'], 'attendances_session_stagiaire_idx');
        });

        Schema::table('seances', function (Blueprint $table) {
            // Composite index to make find-or-create fast
            $table->index(['classe_id', 'date_session', 'time_block_id'], 'seances_classe_date_block_idx');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex('attendances_status_idx');
            $table->dropIndex('attendances_created_by_user_id_idx');
            $table->dropIndex('attendances_session_stagiaire_idx');
        });

        Schema::table('seances', function (Blueprint $table) {
            $table->dropIndex('seances_classe_date_block_idx');
        });
    }
};
