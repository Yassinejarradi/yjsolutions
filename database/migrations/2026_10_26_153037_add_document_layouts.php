<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mise en page modifiable (voir App\Support\DocumentLayout) :
     * le modèle par type de document dans les Paramètres, et les changements propres à chaque devis.
     */
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->json('document_layouts')->nullable()->after('quote_notes');
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->json('layout')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn('layout');
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('document_layouts');
        });
    }
};
