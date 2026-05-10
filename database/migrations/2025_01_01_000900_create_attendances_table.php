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
        if (!Schema::hasTable('attendances')) {
            Schema::create('attendances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('session_id')->constrained('sessions')->onDelete('cascade');
                $table->foreignId('stagiaire_id')->constrained('stagiaires')->onDelete('cascade');
                $table->foreignId('type_absence_id')->constrained('type_absences')->onDelete('cascade');
                $table->text('justification')->nullable();
                $table->string('recorded_by')->nullable();
                $table->dateTime('recorded_at')->nullable();
                $table->timestamps();
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
