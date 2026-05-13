<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('classe_id')->constrained('classes')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['user_id', 'classe_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_classes');
    }
};
