<?php

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Livewire\InvoiceEditor;
use App\Livewire\InvoiceList;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Quote;
use App\Models\Setting;
use App\Models\User;
use App\Support\DocumentSheet;
use App\Support\Word\DocumentWord;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

beforeEach(function () {
    Carbon::setTestNow('2026-10-26 14:32:00');
    $this->actingAs(User::factory()->create());
    Setting::current()->update(['payment_terms_days' => 30, 'invoice_notes' => 'Pénalités de retard : 1 % par mois.']);
});

function invoiceLine(string $description, string $quantity, string $price): array
{
    return ['key' => uniqid(), 'description' => $description, 'quantity' => $quantity, 'unit_price' => $price];
}

test('les pages des factures demandent d’être connecté', function () {
    auth()->logout();

    $this->get('/factures')->assertRedirect('/connexion');
    $this->get('/factures/nouveau')->assertRedirect('/connexion');
});

test('le menu montre maintenant « Factures »', function () {
    $this->get('/tableau-de-bord')->assertSee('Factures');
    $this->get('/factures')->assertOk()->assertSee('+ Nouvelle facture');
});

test('une nouvelle facture reprend les Paramètres', function () {
    Livewire::test(InvoiceEditor::class)
        ->assertSet('issueDate', '26/10/2026')
        ->assertSet('dueDate', '25/11/2026')
        ->assertSet('notes', 'Pénalités de retard : 1 % par mois.')
        ->assertSet('layout.labels.title', 'FACTURE')
        ->assertSet('layout.labels.client', 'Facturer à')
        ->assertSet('layout.show.client_signature', false)
        ->assertSee('Émettre la facture')
        ->assertSee('attribué à l’émission');
});

test('choisir un client fixe l’échéance selon son délai de paiement', function () {
    $client = Client::factory()->create(['payment_terms_days' => 60]);

    Livewire::test(InvoiceEditor::class)
        ->call('chooseClient', $client->id)
        ->assertSet('dueDate', '25/12/2026');
});

test('le brouillon de facture s’enregistre avec ses lignes et ses totaux', function () {
    $client = Client::factory()->create();

    Livewire::test(InvoiceEditor::class)
        ->set('clientId', $client->id)
        ->set('reference', 'BC-2026-45')
        ->set('lines', [invoiceLine('Conseil', '2', '1 500')])
        ->call('save')
        ->assertReturned(true)
        ->assertSee('Enregistré à 14:32');

    $invoice = Invoice::sole();
    expect($invoice->isDraft())->toBeTrue()
        ->and($invoice->reference)->toBe('BC-2026-45')
        ->and($invoice->total_ttc)->toBe(360000);
});

test('émettre la facture attribue le numéro et ouvre sa page', function () {
    $component = Livewire::test(InvoiceEditor::class)
        ->set('clientId', Client::factory()->create()->id)
        ->set('lines', [invoiceLine('Conseil', '1', '1000')])
        ->call('issue');

    $invoice = Invoice::sole();
    $component->assertRedirect(route('invoices.show', $invoice));
    expect($invoice->number)->toBe('FAC2026-001')
        ->and($invoice->status)->toBe(InvoiceStatus::Issued);
});

test('une facture sans client ne peut pas être émise', function () {
    Livewire::test(InvoiceEditor::class)
        ->set('lines', [invoiceLine('Conseil', '1', '1000')])
        ->call('issue')
        ->assertHasErrors(['clientId'])
        ->assertSee('Choisissez le client avant d’émettre la facture.');
});

test('l’éditeur prévient et refuse une date antérieure à la dernière facture émise', function () {
    Invoice::factory()->issued()->create(['issue_date' => '2026-10-20', 'due_date' => '2026-11-19']);

    Livewire::test(InvoiceEditor::class)
        ->set('clientId', Client::factory()->create()->id)
        ->set('issueDate', '15/10/2026')
        ->assertSee('antérieure à la dernière facture émise (20/10/2026)')
        ->set('lines', [invoiceLine('Conseil', '1', '1000')])
        ->call('issue')
        ->assertHasErrors(['send']);

    expect(Invoice::whereNotNull('number')->count())->toBe(1);
});

test('une facture émise n’est plus modifiable ni supprimable', function () {
    $invoice = Invoice::factory()->issued()->create();

    Livewire::test(InvoiceEditor::class, ['invoice' => $invoice])->assertForbidden();
    $this->get("/factures/{$invoice->id}")->assertOk()->assertDontSee('+ Ajouter une ligne')->assertSee('n’est plus modifiable');
    $this->delete("/factures/{$invoice->id}")->assertSessionHas('error');
    expect(Invoice::find($invoice->id))->not->toBeNull();
});

test('un brouillon de facture peut être supprimé', function () {
    $draft = Invoice::factory()->withLines()->create();

    $this->delete("/factures/{$draft->id}")->assertRedirect('/factures');
    expect(Invoice::find($draft->id))->toBeNull();
});

test('« Marquer comme payée » et le paiement partiel depuis la page de la facture', function () {
    $invoice = Invoice::factory()->issued()->create(); // 1 200,00 TTC

    $this->get("/factures/{$invoice->id}")->assertSee('Marquer comme payée')->assertSee('Paiement partiel ou autre mode');

    $this->post("/factures/{$invoice->id}/paiements", ['paid_on' => '20/10/2026', 'amount' => '400,50', 'method' => 'cheque', 'reference' => 'CHQ 987'])
        ->assertSessionHas('success', 'Paiement de 400,50 MAD enregistré. Reste à payer : 799,50 MAD.');
    expect($invoice->fresh()->displayStatus())->toBe(InvoiceStatus::PartiallyPaid);

    $this->get("/factures/{$invoice->id}")->assertSee('CHQ 987')->assertSee('Chèque')->assertSee('799,50 MAD');

    $this->post("/factures/{$invoice->id}/paiements", ['paid_on' => '26/10/2026', 'amount' => '799,50', 'method' => 'virement'])
        ->assertSessionHas('success');
    expect($invoice->fresh()->displayStatus())->toBe(InvoiceStatus::Paid);
    $this->get("/factures/{$invoice->id}")->assertSee('Payée ✓')->assertDontSee('Marquer comme payée');
});

test('le formulaire de paiement refuse un montant trop élevé ou une date future', function () {
    $invoice = Invoice::factory()->issued()->create();

    $this->post("/factures/{$invoice->id}/paiements", ['paid_on' => '26/10/2026', 'amount' => '1 500', 'method' => 'virement'])
        ->assertSessionHasErrors(['amount' => 'Le montant dépasse le reste à payer (1 200,00 MAD).']);
    $this->post("/factures/{$invoice->id}/paiements", ['paid_on' => '27/10/2026', 'amount' => '100', 'method' => 'virement'])
        ->assertSessionHasErrors(['paid_on']);
    $this->post("/factures/{$invoice->id}/paiements", ['paid_on' => '26/10/2026', 'amount' => 'cent', 'method' => 'virement'])
        ->assertSessionHasErrors(['amount']);

    expect($invoice->fresh()->amount_paid)->toBe(0);
});

test('un paiement saisi par erreur peut être supprimé', function () {
    $invoice = Invoice::factory()->issued()->create();
    $payment = $invoice->fresh()->recordPayment(today(), 10000, PaymentMethod::Cash);

    $this->delete("/factures/{$invoice->id}/paiements/{$payment->id}")->assertSessionHas('success');
    expect($invoice->fresh()->amount_paid)->toBe(0);
});

test('annuler une facture depuis sa page', function () {
    $invoice = Invoice::factory()->issued()->create();

    $this->patch("/factures/{$invoice->id}/annuler")->assertSessionHas('success');

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Cancelled);
    $this->get("/factures/{$invoice->id}")->assertSee('Annulée')->assertDontSee('Marquer comme payée');
});

test('« Convertir en facture » depuis la page du devis', function () {
    $quote = Quote::factory()->sent()->create();

    $this->get("/devis/{$quote->id}")->assertSee('Convertir en facture');
    $this->post("/devis/{$quote->id}/convertir")->assertRedirect()->assertSessionHas('success');

    $invoice = Invoice::sole();
    expect($invoice->quote_id)->toBe($quote->id);
    $this->get("/devis/{$quote->id}")->assertDontSee('Convertir en facture')->assertSee('Voir la facture');

    // Un deuxième clic ouvre la facture existante.
    $this->post("/devis/{$quote->id}/convertir")->assertRedirect(route('invoices.show', $invoice));
    expect(Invoice::count())->toBe(1);
});

test('la liste des factures filtre et totalise', function () {
    $a = Invoice::factory()->issued()->create(['client_id' => Client::factory()->create(['name' => 'Atlas'])]);
    $b = Invoice::factory()->issued()->create(['client_id' => Client::factory()->create(['name' => 'Boréal'])]);
    $b->fresh()->recordPayment(today(), 120000, PaymentMethod::Transfer);
    Invoice::factory()->withLines()->create();

    $ids = fn (array $filters) => tap(Livewire::test(InvoiceList::class), fn ($c) => collect($filters)->each(fn ($v, $k) => $c->set($k, $v)))
        ->viewData('invoices')->pluck('id')->sort()->values()->all();

    expect($ids(['status' => 'paid']))->toBe([$b->id])
        ->and($ids(['status' => 'issued']))->toBe([$a->id])
        ->and($ids(['search' => 'atl']))->toBe([$a->id]);

    Livewire::test(InvoiceList::class)->assertSeeInOrder(['3 factures', 'facturé 2 400,00 MAD TTC', 'reste à payer 1 200,00 MAD']);
});

test('la fiche client montre ses factures et ses totaux', function () {
    $client = Client::factory()->create();
    $invoice = Invoice::factory()->issued()->create(['client_id' => $client->id]);

    $this->get("/clients/{$client->id}")
        ->assertSee('FAC2026-001')
        ->assertSee('+ Nouvelle facture')
        ->assertSee('1 200,00 MAD')
        ->assertDontSee('Aucune facture pour ce client.');
});

test('écran, PDF et Word d’une facture montrent les mêmes montants', function () {
    $invoice = Invoice::factory()->withLines([['Audit', '3', 33333], ['Suivi', '1.5', 12345]])->create(['discount_percent' => '7.25', 'vat_rate' => 14]);
    $invoice->issue();
    $invoice->fresh()->recordPayment(today(), 50000, PaymentMethod::Transfer);

    $sheet = DocumentSheet::forInvoice($invoice->fresh());
    $texts = array_merge(array_column($sheet->lines, 'amount'), array_column($sheet->totalRows, 1), [$sheet->amount, $sheet->words]);

    $pdf = view('pdf.document', ['sheet' => $sheet])->render();
    $screen = $this->get("/factures/{$invoice->id}")->assertOk();
    $path = DocumentWord::save($sheet);
    $zip = new ZipArchive;
    $zip->open($path);
    $word = $zip->getFromName('word/document.xml');
    $zip->close();
    unlink($path);

    foreach ($texts as $text) {
        expect($pdf)->toContain(e($text))
            ->and($word)->toContain(htmlspecialchars($text, ENT_XML1));
        $screen->assertSee($text);
    }

    $this->get("/factures/{$invoice->id}/pdf")->assertOk()->assertHeader('Content-Type', 'application/pdf');
    $this->get("/factures/{$invoice->id}/word")->assertOk()->assertDownload('Facture_FAC2026-001.docx');
});

test('la mise en page des factures a son propre modèle', function () {
    $invoice = Invoice::factory()->withLines()->create();

    Livewire::test(InvoiceEditor::class, ['invoice' => $invoice])
        ->set('layout.labels.title', 'NOTE D’HONORAIRES')
        ->call('saveLayoutAsTemplate');

    expect(Setting::current()->document_layouts['invoice']['labels']['title'])->toBe('NOTE D’HONORAIRES')
        ->and(Quote::newDraft()->resolvedLayout()['labels']['title'])->toBe('DEVIS');

    $this->get('/parametres/documents')->assertSee('Mise en page des factures');
    $this->delete('/parametres/documents/mise-en-page/invoice')->assertSessionHas('success');
    expect(Setting::current()->document_layouts)->toBeNull();
});
