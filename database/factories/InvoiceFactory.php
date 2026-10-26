<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'issue_date' => today(),
            'due_date' => today()->addDays(30),
            'reference' => '',
            'vat_rate' => 20,
        ];
    }

    /**
     * Ajoute des lignes après la création (les totaux sont recalculés).
     *
     * @param  list<array{0: string, 1: string, 2: int|null}>  $lines  [désignation, quantité, prix en centimes]
     */
    public function withLines(array $lines = [['Mission de conseil', '2', 250000]]): static
    {
        return $this->afterCreating(fn (Invoice $invoice) => $invoice->replaceLines(array_map(fn (array $l) => [
            'description' => $l[0], 'quantity' => $l[1], 'unit_price' => $l[2],
        ], $lines)));
    }

    /** Facture émise, avec numéro (passe par Invoice::issue()). */
    public function issued(): static
    {
        return $this->afterCreating(function (Invoice $invoice) {
            if ($invoice->lines()->doesntExist()) {
                $invoice->replaceLines([['description' => 'Mission de conseil', 'quantity' => '1', 'unit_price' => 100000]]);
            }
            $invoice->issue();
        });
    }
}
