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
        Schema::create('programmes', function (Blueprint $table) {
            $table->id();
            $table->string('code_diplome')->unique();
            $table->text('libelle_long')->nullable();
            $table->foreignId('filiere_id')->constrained('filieres')->onDelete('cascade');
            $table->foreignId('niveau_id')->constrained('niveau_formations')->onDelete('cascade');
            $table->integer('annee')->nullable();
            $table->integer('saison')->nullable();
            $table->boolean('is_cds')->default(false);
            $table->timestamps();
            $table->index('code_diplome');
            $table->index('filiere_id');
            $table->index('niveau_id');
            $table->index('saison');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('programmes');
    }
};
