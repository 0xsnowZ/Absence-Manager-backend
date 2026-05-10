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
        if (!Schema::hasColumn('sessions', 'time_block_id')) {
            Schema::table('sessions', function (Blueprint $table) {
                // Add column without referencing a specific column position to avoid errors
                $table->foreignId('time_block_id')->nullable()->constrained('time_blocks')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('sessions', 'time_block_id')) {
            Schema::table('sessions', function (Blueprint $table) {
                $table->dropConstrainedForeignId('time_block_id');
            });
        }
    }
};
