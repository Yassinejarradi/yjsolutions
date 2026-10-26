<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lignes des devis et des factures (relation polymorphique « document »).
     */
    public function up(): void
    {
        Schema::create('document_lines', function (Blueprint $table) {
            $table->id();
            $table->morphs('document');
            $table->unsignedSmallInteger('position');
            $table->text('description');
            $table->decimal('quantity', 12, 3)->default(1);
            $table->bigInteger('unit_price')->nullable(); // centimes HT
            $table->bigInteger('amount')->default(0);     // centimes HT, calculé
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_lines');
    }
};
