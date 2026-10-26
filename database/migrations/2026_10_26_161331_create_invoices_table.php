<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Factures. Statuts enregistrés : brouillon, émise, annulée.
     * « Partiellement payée », « Payée » et « En retard » sont calculés (paiements et échéance).
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->nullable()->unique();
            $table->foreignId('client_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('quote_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->default('draft')->index();

            $table->date('issue_date');
            $table->date('due_date');
            $table->string('reference', 100)->default(''); // N° de bon de commande

            $table->unsignedTinyInteger('vat_rate');
            $table->decimal('discount_percent', 5, 2)->default(0);

            // Totaux en centimes, recalculés par DocumentCalculator à chaque enregistrement.
            $table->bigInteger('subtotal')->default(0);
            $table->bigInteger('discount')->default(0);
            $table->bigInteger('total_ht')->default(0);
            $table->bigInteger('vat')->default(0);
            $table->bigInteger('total_ttc')->default(0);
            // Somme des paiements, tenue à jour avec la table payments (filtres et totaux rapides).
            $table->bigInteger('amount_paid')->default(0);

            $table->text('notes')->nullable();
            $table->json('layout')->nullable();
            $table->json('client_snapshot')->nullable();
            $table->json('company_snapshot')->nullable();

            $table->timestamp('issued_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index('issue_date');
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
