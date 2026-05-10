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
        if (!Schema::hasTable('time_blocks')) {
            Schema::create('time_blocks', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('label');
                $table->time('heure_debut');
                $table->time('heure_fin');
                $table->timestamps();
                $table->index('code');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_blocks');
    }
};
