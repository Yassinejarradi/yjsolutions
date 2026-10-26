<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Devis. Un brouillon n'a ni numéro ni snapshot ; à l'envoi, le numéro est attribué
     * et les informations du client et de la société sont copiées (snapshots).
     */
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->nullable()->unique();
            $table->foreignId('client_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('draft')->index();

            $table->date('issue_date');
            $table->date('valid_until');
            $table->string('reference', 100)->default('');

            $table->unsignedTinyInteger('vat_rate');
            $table->decimal('discount_percent', 5, 2)->default(0);

            // Totaux en centimes, recalculés par DocumentCalculator à chaque enregistrement.
            $table->bigInteger('subtotal')->default(0);
            $table->bigInteger('discount')->default(0);
            $table->bigInteger('total_ht')->default(0);
            $table->bigInteger('vat')->default(0);
            $table->bigInteger('total_ttc')->default(0);

            $table->string('payment_terms')->default('');
            $table->string('completion_delay')->default('');
            $table->text('notes')->nullable();

            $table->json('client_snapshot')->nullable();
            $table->json('company_snapshot')->nullable();

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index('issue_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};
