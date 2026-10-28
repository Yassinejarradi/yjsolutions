<?php

use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Models\User;
use App\Support\Revenue;
use Illuminate\Support\Carbon;

beforeEach(function () {
    Carbon::setTestNow('2026-10-15 10:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

/** Facture émise à une date, 1 000,00 MAD HT par unité. */
function invoiceOn(string $date, string $quantity = '1'): Invoice
{
    return Invoice::factory()
        ->withLines([['Mission', $quantity, 100000]])
        ->issued()
        ->create(['issue_date' => $date, 'due_date' => Carbon::parse($date)->addDays(30)]);
}

test('le CA mensuel couvre les 12 derniers mois, mois en cours compris', function () {
    invoiceOn('2025-10-31');            // hors période
    invoiceOn('2025-11-02', '2');
    invoiceOn('2026-03-10');
    invoiceOn('2026-10-01', '3');
    invoiceOn('2026-10-12');
    Invoice::factory()->withLines()->create(['issue_date' => '2026-10-13']); // brouillon : ignoré

    $monthly = Revenue::monthly(12);

    expect($monthly)->toHaveCount(12)
        ->and($monthly[0]['month']->format('Y-m'))->toBe('2025-11')
        ->and($monthly[0]['total'])->toBe(200000)
        ->and($monthly[4]['total'])->toBe(100000)
        ->and($monthly[11]['month']->format('Y-m'))->toBe('2026-10')
        ->and($monthly[11]['total'])->toBe(400000)
        ->and(array_sum(array_column($monthly, 'total')))->toBe(700000);
});

test('une facture annulée ne compte pas dans le chiffre d’affaires', function () {
    invoiceOn('2026-10-01');
    invoiceOn('2026-10-02')->cancel();

    expect(Revenue::invoiced(Carbon::parse('2026-10-01'), Carbon::parse('2026-10-31')))->toBe(100000);
});

test('l’encaissé suit la date des paiements', function () {
    $invoice = invoiceOn('2025-12-20');
    $invoice->recordPayment(Carbon::parse('2025-12-28'), 50000, PaymentMethod::Transfer);
    $invoice->recordPayment(Carbon::parse('2026-01-05'), 70000, PaymentMethod::Transfer);

    expect(Revenue::collected(Carbon::parse('2026-01-01'), Carbon::parse('2026-12-31')))->toBe(70000);
});

test('le tableau de bord affiche le CA, le graphique et les points à suivre', function () {
    invoiceOn('2026-02-10', '5');
    invoiceOn('2026-10-03', '2');

    $this->actingAs(User::factory()->create())
        ->get('/tableau-de-bord')
        ->assertOk()
        ->assertSeeInOrder(['Chiffre d’affaires d’octobre', '2 000,00 MAD'])
        ->assertSeeInOrder(['Chiffre d’affaires 2026', '7 000,00 MAD'])
        ->assertSee('Chiffre d’affaires HT sur 12 mois')
        ->assertSee('février 2026')
        ->assertSee('Factures en retard')
        ->assertSee('Devis en attente')
        ->assertDontSee('Reste à encaisser');
});
