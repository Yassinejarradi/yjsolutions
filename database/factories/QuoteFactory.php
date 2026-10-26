<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Quote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quote>
 */
class QuoteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'issue_date' => today(),
            'valid_until' => today()->addDays(30),
            'reference' => '',
            'vat_rate' => 20,
            'payment_terms' => '50 % à la commande, solde à la livraison',
            'completion_delay' => '3 semaines',
        ];
    }

    /**
     * Ajoute des lignes après la création (les totaux sont recalculés).
     *
     * @param  list<array{0: string, 1: string, 2: int|null}>  $lines  [désignation, quantité, prix en centimes]
     */
    public function withLines(array $lines = [['Mission de conseil', '2', 250000]]): static
    {
        return $this->afterCreating(fn (Quote $quote) => $quote->replaceLines(array_map(fn (array $l) => [
            'description' => $l[0], 'quantity' => $l[1], 'unit_price' => $l[2],
        ], $lines)));
    }

    /** Devis envoyé, avec numéro (passe par Quote::send()). */
    public function sent(): static
    {
        return $this->afterCreating(function (Quote $quote) {
            if ($quote->lines()->doesntExist()) {
                $quote->replaceLines([['description' => 'Mission de conseil', 'quantity' => '1', 'unit_price' => 100000]]);
            }
            $quote->send();
        });
    }
}
