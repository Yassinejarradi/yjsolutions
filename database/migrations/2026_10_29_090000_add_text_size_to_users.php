<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Taille du texte de l'interface choisie par chaque utilisateur : normal, large, xlarge.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('text_size', 10)->default('normal')->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('text_size');
        });
    }
};
