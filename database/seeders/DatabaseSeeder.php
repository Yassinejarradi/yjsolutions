<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Données réelles uniquement. Aucun utilisateur n'est créé ici :
     * utiliser « php artisan user:create » pour créer le premier compte.
     */
    public function run(): void
    {
        $this->call(CompanySettingsSeeder::class);
    }
}
