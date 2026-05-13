<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classe_id')->constrained('classes')->onDelete('cascade');
            $table->date('date_session');
            $table->time('heure_debut')->nullable();
            $table->time('heure_fin')->nullable();
            $table->foreignId('time_block_id')->nullable()->constrained('time_blocks')->nullOnDelete();
            $table->string('lieu')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();
            $table->index('classe_id');
            $table->index('date_session');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seances');
    }
};
