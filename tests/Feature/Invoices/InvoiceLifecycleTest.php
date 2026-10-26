<?php

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\QuoteStatus;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Quote;
use App\Models\Sequence;
use App\Models\Setting;
use App\Support\DocumentSheet;
use Illuminate\Support\Carbon;

beforeEach(function () {
    Carbon::setTestNow('2026-10-26 10:00:00');
});

test('une facture brouillon n’a pas de numéro', function () {
    $invoice = Invoice::factory()->withLines()->create();

    expect($invoice->number)->toBeNull()
        ->and($invoice->displayStatus())->toBe(InvoiceStatus::Draft);
});

test('la numérotation des factures est sans trou, par année, distincte des devis', function () {
    Quote::factory()->sent()->create();
    $a = Invoice::factory()->withLines()->create();
    $b = Invoice::factory()->withLines()->create();

    $a->issue();
    $b->issue();

    expect($a->fresh()->number)->toBe('FAC2026-001')
        ->and($b->fresh()->number)->toBe('FAC2026-002')
        ->and(Sequence::where('type', 'invoice')->value('last_number'))->toBe(2)
        ->and(Sequence::where('type', 'quote')->value('last_number'))->toBe(1);
});

test('le préfixe des factures vient des Paramètres', function () {
    Setting::current()->update(['invoice_prefix' => 'F']);
    $invoice = Invoice::factory()->issued()->create();

    expect($invoice->fresh()->number)->toBe('F2026-001');
});

test('une facture ne peut pas être datée avant la dernière facture émise', function () {
    Invoice::factory()->issued()->create(['issue_date' => '2026-10-20', 'due_date' => '2026-11-19']);
    $earlier = Invoice::factory()->withLines()->create(['issue_date' => '2026-10-10', 'due_date' => '2026-11-09']);

    expect(fn () => $earlier->issue())->toThrow(LogicException::class, 'antérieure à la dernière facture émise (20/10/2026)');
    expect($earlier->fresh()->number)->toBeNull()
        ->and(Sequence::where('type', 'invoice')->value('last_number'))->toBe(1);

    $sameDay = Invoice::factory()->withLines()->create(['issue_date' => '2026-10-20', 'due_date' => '2026-11-19']);
    $sameDay->issue();
    expect($sameDay->fresh()->number)->toBe('FAC2026-002');
});

test('une facture émise ne peut pas être émise deux fois', function () {
    $invoice = Invoice::factory()->withLines()->create();
    $stale = Invoice::find($invoice->id);
    $invoice->issue();

    expect(fn () => $stale->issue())->toThrow(LogicException::class, 'Seul un brouillon peut être émis.');
});

test('l’échéance d’une nouvelle facture suit le délai de paiement du client', function () {
    Setting::current()->update(['payment_terms_days' => 30]);
    $client = Client::factory()->create(['payment_terms_days' => 60]);

    expect(Invoice::newDraft($client)->due_date->format('d/m/Y'))->toBe('25/12/2026')
        ->and(Invoice::newDraft()->due_date->format('d/m/Y'))->toBe('25/11/2026');
});

test('paiements partiels puis complets : statuts et reste à payer', function () {
    $invoice = Invoice::factory()->withLines([['Conseil', '1', 100000]])->create(); // 1 200,00 TTC
    $invoice->issue();
    $invoice = $invoice->fresh();

    expect($invoice->displayStatus())->toBe(InvoiceStatus::Issued)
        ->and($invoice->balance())->toBe(120000);

    $invoice->recordPayment(today(), 50000, PaymentMethod::Cheque, 'CHQ 123');
    $invoice = $invoice->fresh();
    expect($invoice->amount_paid)->toBe(50000)
        ->and($invoice->balance())->toBe(70000)
        ->and($invoice->displayStatus())->toBe(InvoiceStatus::PartiallyPaid);

    $invoice->recordPayment(today(), 70000, PaymentMethod::Transfer);
    expect($invoice->fresh()->displayStatus())->toBe(InvoiceStatus::Paid)
        ->and($invoice->fresh()->balance())->toBe(0);
});

test('un paiement ne peut pas dépasser le reste à payer, ni porter sur un brouillon', function () {
    $draft = Invoice::factory()->withLines()->create();
    expect(fn () => $draft->recordPayment(today(), 100, PaymentMethod::Cash))->toThrow(LogicException::class);

    $invoice = Invoice::factory()->issued()->create(); // 1 200,00 TTC
    expect(fn () => $invoice->fresh()->recordPayment(today(), 120001, PaymentMethod::Cash))->toThrow(LogicException::class)
        ->and(fn () => $invoice->fresh()->recordPayment(today(), 0, PaymentMethod::Cash))->toThrow(LogicException::class);
    expect($invoice->fresh()->amount_paid)->toBe(0);
});

test('supprimer un paiement remet le reste à payer', function () {
    $invoice = Invoice::factory()->issued()->create();
    $payment = $invoice->fresh()->recordPayment(today(), 20000, PaymentMethod::Cash);

    $invoice->fresh()->removePayment($payment);

    expect($invoice->fresh()->amount_paid)->toBe(0)
        ->and($invoice->payments()->count())->toBe(0);
});

test('« En retard » est calculé après l’échéance tant qu’il reste un solde', function () {
    $invoice = Invoice::factory()->issued()->create(['issue_date' => '2026-09-01', 'due_date' => '2026-10-01']);

    expect($invoice->fresh()->displayStatus())->toBe(InvoiceStatus::Overdue);

    $invoice->fresh()->recordPayment(today(), 20000, PaymentMethod::Transfer);
    expect($invoice->fresh()->displayStatus())->toBe(InvoiceStatus::Overdue);

    $invoice->fresh()->recordPayment(today(), $invoice->fresh()->balance(), PaymentMethod::Transfer);
    expect($invoice->fresh()->displayStatus())->toBe(InvoiceStatus::Paid);
});

test('les filtres de statut retrouvent les statuts calculés', function () {
    $draft = Invoice::factory()->withLines()->create();
    $issued = Invoice::factory()->issued()->create();
    $partial = Invoice::factory()->issued()->create();
    $partial->fresh()->recordPayment(today(), 1000, PaymentMethod::Cash);
    $paid = Invoice::factory()->issued()->create();
    $paid->fresh()->recordPayment(today(), $paid->fresh()->balance(), PaymentMethod::Cash);
    $cancelled = Invoice::factory()->issued()->create();
    $cancelled->fresh()->cancel();
    Carbon::setTestNow('2026-12-31');
    $overdue = $issued; // échéance dépassée au 31/12
    Carbon::setTestNow('2026-10-26');

    $ids = fn (InvoiceStatus $s) => Invoice::query()->withDisplayStatus($s)->pluck('id')->sort()->values()->all();

    expect($ids(InvoiceStatus::Draft))->toBe([$draft->id])
        ->and($ids(InvoiceStatus::Issued))->toBe([$issued->id])
        ->and($ids(InvoiceStatus::PartiallyPaid))->toBe([$partial->id])
        ->and($ids(InvoiceStatus::Paid))->toBe([$paid->id])
        ->and($ids(InvoiceStatus::Cancelled))->toBe([$cancelled->id]);

    Carbon::setTestNow('2026-12-31');
    expect($ids(InvoiceStatus::Overdue))->toBe([$overdue->id, $partial->id]);
});

test('une facture émise peut être annulée : elle garde son numéro et son PDF porte « ANNULÉE »', function () {
    $invoice = Invoice::factory()->issued()->create();
    $invoice->fresh()->cancel();

    $invoice = $invoice->fresh();
    expect($invoice->status)->toBe(InvoiceStatus::Cancelled)
        ->and($invoice->number)->toBe('FAC2026-001')
        ->and(DocumentSheet::forInvoice($invoice)->watermark)->toBe('ANNULÉE');

    expect(fn () => $invoice->cancel())->toThrow(LogicException::class);
});

test('une facture avec des paiements ne peut pas être annulée', function () {
    $invoice = Invoice::factory()->issued()->create();
    $invoice->fresh()->recordPayment(today(), 1000, PaymentMethod::Cash);

    expect(fn () => $invoice->fresh()->cancel())->toThrow(LogicException::class, 'supprimez-les avant');
});

test('un devis envoyé se convertit en facture brouillon, une seule fois', function () {
    $quote = Quote::factory()->withLines([['Audit', '2', 300000], ['Rapport', '1', 150000]])->create(['discount_percent' => '5', 'vat_rate' => 14]);
    $quote->send();

    $invoice = Invoice::fromQuote($quote->fresh());

    expect($invoice->isDraft())->toBeTrue()
        ->and($invoice->quote_id)->toBe($quote->id)
        ->and($invoice->client_id)->toBe($quote->client_id)
        ->and($invoice->vat_rate)->toBe(14)
        ->and($invoice->lines->pluck('description')->all())->toBe(['Audit', 'Rapport'])
        ->and($invoice->total_ttc)->toBe($quote->fresh()->total_ttc)
        ->and($quote->fresh()->status)->toBe(QuoteStatus::Accepted)
        ->and($quote->fresh()->canBeInvoiced())->toBeFalse();

    expect(fn () => Invoice::fromQuote($quote->fresh()))->toThrow(LogicException::class);
});

test('un devis brouillon ou refusé ne se convertit pas', function () {
    $draft = Quote::factory()->withLines()->create();
    $refused = Quote::factory()->sent()->create();
    $refused->fresh()->refuse();

    expect($draft->canBeInvoiced())->toBeFalse()
        ->and($refused->fresh()->canBeInvoiced())->toBeFalse();
});

test('le client a des documents, son total facturé et son reste à payer', function () {
    $client = Client::factory()->create();
    $a = Invoice::factory()->issued()->create(['client_id' => $client->id]);          // 1 200,00
    $b = Invoice::factory()->issued()->create(['client_id' => $client->id]);          // 1 200,00
    $b->fresh()->recordPayment(today(), 50000, PaymentMethod::Transfer);
    $cancelled = Invoice::factory()->issued()->create(['client_id' => $client->id]);
    $cancelled->fresh()->cancel();
    Invoice::factory()->withLines()->create(['client_id' => $client->id]);              // brouillon : non compté

    expect($client->hasDocuments())->toBeTrue()
        ->and($client->billedTotal())->toBe(240000)
        ->and($client->outstandingBalance())->toBe(190000);
});

test('le PDF d’une facture partiellement payée montre le solde exigible', function () {
    $invoice = Invoice::factory()->issued()->create(); // 1 200,00 TTC
    $invoice->fresh()->recordPayment(today(), 20000, PaymentMethod::Transfer);

    $sheet = DocumentSheet::forInvoice($invoice->fresh());
    $rows = collect($sheet->totalRows)->mapWithKeys(fn ($r) => [$r[0] => $r[1]]);

    expect($sheet->title)->toBe('FACTURE')
        ->and($sheet->amountLabel)->toBe('Solde exigible')
        ->and($sheet->amount)->toBe('1 000,00 MAD')
        ->and($rows['Total TTC'])->toBe('1 200,00 MAD')
        ->and($rows['Déjà payé'])->toBe('-200,00 MAD')
        ->and($rows['Solde exigible'])->toBe('1 000,00 MAD')
        ->and($sheet->words)->toBe('Mille deux cents dirhams TTC')
        ->and($sheet->clientSignature)->toBeNull()
        ->and($sheet->companySignature)->not->toBeNull();
});

test('la ligne bancaire des Paramètres est imprimée sur la facture', function () {
    Setting::current()->update(['bank_name' => 'Attijariwafa Bank', 'bank_branch' => 'Agence Ibn Tachefine', 'rib' => '007780000123456789012345']);
    $sheet = DocumentSheet::forInvoice(Invoice::factory()->issued()->create()->fresh());

    expect($sheet->terms)->toBe([['Paiement par virement bancaire :', 'Attijariwafa Bank – Agence Ibn Tachefine – RIB : 007780000123456789012345']]);
});
