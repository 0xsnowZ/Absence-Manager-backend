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
        Schema::table('attendances', function (Blueprint $table) {
            // Add status column with enum values and default
            $table->enum('status', ['non_justifie', 'justifie', 'retard', 'absence_excusee'])
                ->default('non_justifie')
                ->nullable(false)
                ->after('type_absence_id');

            // Track who created the absence
            $table->unsignedBigInteger('created_by_user_id')
                ->nullable()
                ->after('status');
            
            // Track who last updated the absence
            $table->unsignedBigInteger('updated_by_user_id')
                ->nullable()
                ->after('created_by_user_id');

            // Track when the absence was justified
            $table->dateTime('justified_at')
                ->nullable()
                ->after('updated_by_user_id');
        });

        // Add foreign keys after columns are created
        Schema::table('attendances', function (Blueprint $table) {
            $table->foreign('created_by_user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->foreign('updated_by_user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['created_by_user_id']);
            $table->dropForeign(['updated_by_user_id']);
            $table->dropColumn(['status', 'created_by_user_id', 'updated_by_user_id', 'justified_at']);
        });
    }
};
