<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stagiaires', function (Blueprint $table) {
            $table->string('lieu_naissance')->nullable()->after('date_naissance');
        });
    }

    public function down(): void
    {
        Schema::table('stagiaires', function (Blueprint $table) {
            $table->dropColumn('lieu_naissance');
        });
    }
};
