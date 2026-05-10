<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('attendances')) {
            // Determine referenced column types to ensure compatible FK column types
            $database = env('DB_DATABASE', DB::getDatabaseName());

            $sessionCol = DB::selectOne(
                'SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
                [$database, 'sessions', 'id']
            );

            $stagiaireCol = DB::selectOne(
                'SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
                [$database, 'stagiaires', 'id']
            );

            $typeAbsCol = DB::selectOne(
                'SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
                [$database, 'type_absences', 'id']
            );

            Schema::create('attendances', function (Blueprint $table) use ($sessionCol, $stagiaireCol, $typeAbsCol) {
                $table->id();

                // session_id: match sessions.id type
                if ($sessionCol && strtolower($sessionCol->DATA_TYPE) === 'bigint') {
                    $table->unsignedBigInteger('session_id');
                } else {
                    $table->unsignedInteger('session_id');
                }

                // stagiaire_id: match stagiaires.id type
                if ($stagiaireCol && strtolower($stagiaireCol->DATA_TYPE) === 'bigint') {
                    $table->unsignedBigInteger('stagiaire_id');
                } else {
                    $table->unsignedInteger('stagiaire_id');
                }

                // type_absence_id: match type_absences.id type
                if ($typeAbsCol && strtolower($typeAbsCol->DATA_TYPE) === 'bigint') {
                    $table->unsignedBigInteger('type_absence_id');
                } else {
                    $table->unsignedInteger('type_absence_id');
                }

                $table->text('justification')->nullable();
                $table->string('recorded_by')->nullable();
                $table->dateTime('recorded_at')->nullable();
                $table->timestamps();

                // add foreign keys after columns created
                $table->foreign('session_id')->references('id')->on('sessions')->onDelete('cascade');
                $table->foreign('stagiaire_id')->references('id')->on('stagiaires')->onDelete('cascade');
                $table->foreign('type_absence_id')->references('id')->on('type_absences')->onDelete('cascade');

                $table->unique(['session_id', 'stagiaire_id']);
                $table->index('session_id');
                $table->index('stagiaire_id');
                $table->index('type_absence_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
