<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Paramètres de la société : une seule ligne, modifiable dans « Paramètres ».
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();

            // Identité de la société (copiée dans chaque document au moment de l'émission).
            $table->string('company_name')->default('');
            $table->string('address')->default('');
            $table->string('phone')->default('');
            $table->string('email')->default('');
            $table->string('ice', 30)->default('');
            $table->string('rc', 30)->default('');
            $table->string('if_number', 30)->default('');
            $table->string('tp', 30)->default('');
            $table->string('cnss', 30)->default('');
            $table->string('logo_path')->nullable();

            // Coordonnées bancaires (ligne « Paiement par virement bancaire » des factures).
            $table->string('bank_name')->default('');
            $table->string('bank_branch')->default('');
            $table->string('rib', 40)->default('');

            // Valeurs par défaut des documents.
            $table->string('invoice_prefix', 10)->default('FAC');
            $table->string('quote_prefix', 10)->default('DEV');
            $table->unsignedTinyInteger('default_vat_rate')->default(20);
            $table->unsignedSmallInteger('payment_terms_days')->default(30);
            $table->unsignedSmallInteger('quote_validity_days')->default(30);
            $table->string('quote_payment_terms')->default('');
            $table->text('invoice_notes')->nullable();
            $table->text('quote_notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
