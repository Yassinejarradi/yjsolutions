<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Données réelles de JY Solutions. Modifiables ensuite dans « Paramètres ».
 * Ne remplace que les champs vides : relancer ce seeder n'écrase pas les modifications.
 */
class CompanySettingsSeeder extends Seeder
{
    public function run(): void
    {
        $setting = Setting::current();

        $defaults = [
            'company_name' => 'JY SOLUTIONS',
            'address' => 'N° 276 Bd. Ibn Tachefine, 3ème Étage – Casablanca',
            'phone' => '0661 38 63 48',
            'email' => 'JYsolution66@gmail.com',
            'ice' => '004038025000064',
            'rc' => '748069',
            'if_number' => '73385534',
            'tp' => '31905602',
            'cnss' => '7217245',
            'quote_payment_terms' => '50 % à la commande, solde à la livraison',
        ];

        foreach ($defaults as $field => $value) {
            if (blank($setting->{$field})) {
                $setting->{$field} = $value;
            }
        }

        $setting->save();
    }
}
