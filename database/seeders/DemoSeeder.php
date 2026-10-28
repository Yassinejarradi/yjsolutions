<?php

namespace Database\Seeders;

use App\Enums\PaymentMethod;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Quote;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * Données de démonstration sur les 12 derniers mois : clients, devis, factures et paiements.
 * Pour une présentation ou des essais, jamais sur la vraie base :
 *
 *   php artisan db:seed --class=DemoSeeder
 *
 * Compte créé : demo@jysolutions.ma / motdepasse
 */
class DemoSeeder extends Seeder
{
    private const SERVICES = [
        ["Audit organisationnel\nEntretiens avec la direction et rapport de synthèse", '1', 4500000],
        ['Formation des équipes (par jour)', '3', 600000],
        ['Accompagnement à la réorganisation (par jour)', '5', 800000],
        ["Diagnostic des processus\nCartographie et recommandations", '1', 2800000],
        ['Coaching de dirigeant (par séance)', '4', 350000],
        ['Atelier de cohésion d’équipe', '1', 1200000],
        ['Étude de satisfaction client', '1', 1900000],
        ['Assistance à la mise en place d’indicateurs', '2', 750000],
    ];

    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('Les données de démonstration ne doivent pas être chargées en production.');
        }

        if (Invoice::query()->exists() || Quote::query()->exists()) {
            throw new RuntimeException('La base contient déjà des devis ou des factures : utilisez une base vide pour la démonstration.');
        }

        $this->call(CompanySettingsSeeder::class);
        mt_srand(2026);

        Setting::current()->update([
            'bank_name' => Setting::current()->bank_name ?: 'Attijariwafa Bank',
            'bank_branch' => Setting::current()->bank_branch ?: 'Agence Ibn Tachefine',
            'rib' => Setting::current()->rib ?: '007 780 0001234567890123 45',
        ]);

        User::firstOrCreate(['email' => 'demo@jysolutions.ma'], ['name' => 'Jamal Yassine', 'password' => 'motdepasse']);

        $clients = collect([
            ['name' => 'OCP Group', 'address' => '2-4 rue Al Abtal, Hay Erraha', 'city' => 'Casablanca', 'ice' => '002345678000012', 'contact_name' => 'Mme Alaoui', 'phone' => '0522 23 45 67'],
            ['name' => 'Atlas Industrie SA', 'address' => 'Zone franche, lot 14', 'city' => 'Tanger', 'ice' => '003456789000034', 'contact_name' => 'M. Tazi', 'phone' => '0539 39 12 00'],
            ['name' => 'Bennani Conseil', 'address' => '12 avenue Mohammed V', 'city' => 'Rabat', 'ice' => '001111111000011', 'contact_name' => 'M. Bennani'],
            ['name' => 'Marjane Distribution', 'address' => 'Route de Rabat, Aïn Sebaâ', 'city' => 'Casablanca', 'ice' => '004567890000056', 'contact_name' => 'Mme Idrissi', 'phone' => '0522 66 70 80'],
            ['name' => 'Société Agricole du Souss', 'address' => 'Zone industrielle Tassila', 'city' => 'Agadir', 'ice' => '005678901000078'],
            ['name' => 'Clinique Les Palmiers', 'address' => '45 boulevard Zerktouni', 'city' => 'Casablanca', 'ice' => '006789012000090', 'contact_name' => 'Dr Chraibi'],
        ])->map(fn (array $data) => Client::create($data));

        // Factures sur 12 mois, dans l'ordre des dates (la numérotation suit les dates).
        $start = today()->startOfMonth()->subMonths(11);
        for ($offset = 0; $offset < 12; $offset++) {
            $month = $start->copy()->addMonths($offset);
            $days = collect(range(1, mt_rand(1, 3)))
                ->map(fn () => mt_rand(2, $month->daysInMonth - 2))
                ->sort();

            foreach ($days as $day) {
                $date = $month->copy()->day($day);
                if ($date->gt(today())) {
                    continue;
                }
                $this->invoice($clients->random(), $date);
            }
        }

        // Une facture annulée et un brouillon.
        $last = Invoice::query()->whereNotNull('number')->latest('issue_date')->first();
        $cancelled = $this->invoice($clients->random(), $last->issue_date->copy(), pay: false);
        $cancelled->cancel();
        $draft = Invoice::newDraft($clients->first());
        $draft->save();
        $draft->replaceLines([$this->line(self::SERVICES[1])]);

        // Devis : acceptés, refusés, en attente, expirés et un brouillon.
        foreach ([[-80, 'accept'], [-60, 'refuse'], [-50, null], [-20, 'accept'], [-10, null], [-3, null]] as [$days, $decision]) {
            $quote = Quote::newDraft();
            $quote->fill(['client_id' => $clients->random()->id, 'issue_date' => today()->addDays($days)]);
            $quote->valid_until = $quote->issue_date->copy()->addDays(30);
            $quote->save();
            $quote->replaceLines($this->lines());
            $quote->send();
            if ($decision) {
                $quote->{$decision}();
            }
        }
        $quote = Quote::newDraft();
        $quote->client_id = $clients->last()->id;
        $quote->save();
        $quote->replaceLines($this->lines());
    }

    /** Facture émise à cette date ; payée selon son ancienneté (les plus récentes restent à encaisser). */
    private function invoice(Client $client, Carbon $date, bool $pay = true): Invoice
    {
        $invoice = Invoice::newDraft($client);
        $invoice->fill(['issue_date' => $date, 'due_date' => $date->copy()->addDays(30)])->save();
        $invoice->replaceLines($this->lines());
        $invoice->issue();

        $age = $date->diffInDays(today());
        if (! $pay || $age < 20) {
            return $invoice;
        }

        $method = PaymentMethod::cases()[mt_rand(0, 1)];
        $paidOn = $date->copy()->addDays(min($age, mt_rand(15, 40)));

        if ($age < 60 && mt_rand(0, 1)) {
            // Acompte seulement : reste à payer (en retard si l'échéance est passée).
            $invoice->recordPayment($paidOn, intdiv($invoice->total_ttc, 2), $method, 'Acompte');
        } elseif ($age >= 45 || mt_rand(0, 2)) {
            $invoice->recordPayment($paidOn, $invoice->total_ttc, $method);
        }

        return $invoice;
    }

    /** @return list<array{description: string, quantity: string, unit_price: int}> */
    private function lines(): array
    {
        $keys = (array) array_rand(self::SERVICES, mt_rand(1, 3));

        return array_map(fn (int $key) => $this->line(self::SERVICES[$key]), $keys);
    }

    /** @return array{description: string, quantity: string, unit_price: int} */
    private function line(array $service): array
    {
        return ['description' => $service[0], 'quantity' => $service[1], 'unit_price' => $service[2]];
    }
}
