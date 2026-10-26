<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Clients. Un client qui a des documents n'est jamais supprimé : il est archivé (archived_at).
     */
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('ice', 15)->nullable()->unique();
            $table->string('if_number', 30)->default('');
            $table->string('rc', 30)->default('');
            $table->string('address')->default('');
            $table->string('city', 100)->default('');
            $table->string('phone', 50)->default('');
            $table->string('email')->default('');
            $table->string('contact_name')->default('');

            // Vide : on utilise le délai de paiement des Paramètres.
            $table->unsignedSmallInteger('payment_terms_days')->nullable();
            $table->text('notes')->nullable();

            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
