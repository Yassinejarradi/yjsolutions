<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * « Délai de réalisation » proposé par défaut sur les nouveaux devis.
     */
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('quote_completion_delay')->default('')->after('quote_payment_terms');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('quote_completion_delay');
        });
    }
};
