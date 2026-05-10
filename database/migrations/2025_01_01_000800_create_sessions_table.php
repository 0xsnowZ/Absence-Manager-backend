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
        if (!Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('programme_id')->constrained('programmes')->onDelete('cascade');
                $table->date('date_session');
                $table->time('heure_debut')->nullable();
                $table->time('heure_fin')->nullable();
                $table->string('lieu')->nullable();
                $table->string('created_by')->nullable();
                $table->timestamps();
                $table->index('programme_id');
                $table->index('date_session');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};
