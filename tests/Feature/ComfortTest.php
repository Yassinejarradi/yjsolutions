<?php

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\QuoteStatus;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Quote;
use App\Models\User;
use App\Support\ClientStatement;
use App\Support\PaymentReminder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    Carbon::setTestNow('2026-10-29 10:00');
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

afterEach(function () {
    Carbon::setTestNow();
});

/** Facture émise pour un client, 1 000,00 MAD HT (1 200,00 TTC). */
function issuedInvoice(Client $client, string $date, string $due): Invoice
{
    return Invoice::factory()->for($client)->withLines([['Mission', '1', 100000]])->issued()
        ->create(['issue_date' => $date, 'due_date' => $due]);
}

// ---------- Confirmations ----------

test('plus aucune boîte confirm() du navigateur dans les vues', function () {
    $offenders = collect(File::allFiles(resource_path('views')))
        ->filter(fn ($file) => preg_match('/onsubmit="return confirm|wire:confirm/', $file->getContents()))
        ->map->getRelativePathname();

    expect($offenders->all())->toBe([]);
});

test('la fenêtre de confirmation est présente et les actions définitives la demandent', function () {
    $quote = Quote::factory()->withLines()->create();
    $quote->send();

    $this->get(route('quotes.show', $quote))
        ->assertOk()
        ->assertSee('x-data="confirmDialog"', false)
        ->assertSee('data-confirm-title="Le client a accepté ce devis ?"', false)
        ->assertSee('data-confirm-title="Facturer ce devis maintenant ?"', false);
});

// ---------- Facturer ce devis en un clic ----------

test('« Facturer ce devis » crée et émet la facture tout de suite', function () {
    $quote = Quote::factory()->withLines([['Audit', '2', 150000]])->create();
    $quote->send();

    $response = $this->post(route('quotes.invoice', $quote));

    $invoice = $quote->fresh()->invoice;
    expect($invoice->status)->toBe(InvoiceStatus::Issued)
        ->and($invoice->number)->toBe('FAC2026-001')
        ->and($invoice->total_ttc)->toBe($quote->total_ttc)
        ->and($quote->fresh()->status)->toBe(QuoteStatus::Accepted);
    $response->assertRedirect(route('invoices.show', $invoice))->assertSessionHas('success');

    // Un second clic ne crée pas une deuxième facture.
    $this->post(route('quotes.invoice', $quote))->assertRedirect(route('invoices.show', $invoice));
    expect(Invoice::count())->toBe(1);
});

test('si la facture ne peut pas être émise, le brouillon reste ouvert avec une explication', function () {
    $quote = Quote::factory()->withLines()->create();
    $quote->send();
    $quote->client->forceFill(['archived_at' => now()])->save(); // client archivé : non repris sur la facture

    $this->post(route('quotes.invoice', $quote))->assertSessionHas('error');

    $invoice = $quote->fresh()->invoice;
    expect($invoice->status)->toBe(InvoiceStatus::Draft)->and($invoice->number)->toBeNull();
});

// ---------- Relances ----------

test('le numéro de téléphone est converti pour WhatsApp', function (?string $phone, ?string $expected) {
    expect(PaymentReminder::internationalNumber($phone))->toBe($expected);
})->with([
    ['0661 38 63 48', '212661386348'],
    ['0522-23-45-67', '212522234567'],
    ['661386348', '212661386348'],
    ['+212 661 38 63 48', '212661386348'],
    ['00212661386348', '212661386348'],
    ['+33 6 12 34 56 78', '33612345678'],
    ['', null],
    [null, null],
    ['1234', null],
]);

test('la page « À relancer » liste les retards puis les échéances de la semaine', function () {
    $ocp = Client::factory()->create(['name' => 'OCP Group', 'phone' => '0522 23 45 67', 'contact_name' => 'Mme Alaoui']);
    $late = issuedInvoice($ocp, '2026-09-01', '2026-10-01');          // en retard
    $soon = issuedInvoice($ocp, '2026-10-02', '2026-11-03');          // dans 5 jours
    $later = issuedInvoice($ocp, '2026-10-03', '2026-12-31');         // trop loin
    $paid = issuedInvoice($ocp, '2026-10-04', '2026-10-10');
    $paid->recordPayment(Carbon::parse('2026-10-09'), $paid->total_ttc, PaymentMethod::Transfer);

    $this->get('/relances')
        ->assertOk()
        ->assertSeeInOrder(['En retard', $late->number, '28 jours de retard', 'À échéance', $soon->number, 'dans 5 jours'])
        ->assertDontSee($later->number)
        ->assertDontSee($paid->number)
        ->assertSee('href="tel:0522234567"', false)
        ->assertSee('https://wa.me/212522234567?text=', false);
});

test('le message de relance reprend la facture et le reste à payer', function () {
    $client = Client::factory()->create(['contact_name' => 'M. Tazi']);
    $invoice = issuedInvoice($client, '2026-09-01', '2026-10-01');
    $invoice->recordPayment(Carbon::parse('2026-10-05'), 20000, PaymentMethod::Cheque);

    $message = PaymentReminder::message($invoice->fresh());

    expect($message)->toStartWith('Bonjour M. Tazi,')
        ->toContain("facture {$invoice->number} du 01/09/2026")
        ->toContain('arrivée à échéance le 01/10/2026')
        ->toContain('1 000,00 MAD (sur un total de 1 200,00 MAD)');
});

test('le menu montre le nombre de factures en retard', function () {
    issuedInvoice(Client::factory()->create(), '2026-09-01', '2026-10-01');

    $this->get('/tableau-de-bord')->assertSee('title="Factures en retard">1<', false);
});

// ---------- Relevé de compte ----------

test('le relevé de compte additionne les factures émises et le solde dû', function () {
    $client = Client::factory()->create(['name' => 'Atlas Industrie SA']);
    issuedInvoice($client, '2026-09-01', '2026-10-01');
    $paid = issuedInvoice($client, '2026-09-15', '2026-10-15');
    $paid->recordPayment(Carbon::parse('2026-10-10'), $paid->total_ttc, PaymentMethod::Transfer);
    issuedInvoice($client, '2026-10-01', '2026-10-31')->cancel();
    Invoice::factory()->for($client)->withLines()->create(); // brouillon

    $statement = ClientStatement::for($client);

    expect($statement->rows)->toHaveCount(2)
        ->and($statement->total)->toBe(240000)
        ->and($statement->paid)->toBe(120000)
        ->and($statement->balance)->toBe(120000)
        ->and($statement->rows[0]['late'])->toBeTrue()
        ->and($statement->rows[1]['balance'])->toBe('Payée');

    $html = view('pdf.statement', ['statement' => $statement])->render();
    expect($html)->toContain('RELEVÉ DE COMPTE')->toContain('Atlas Industrie SA')->toContain('1 200,00 MAD');
});

test('le relevé de compte se télécharge en PDF depuis la fiche client', function () {
    $client = Client::factory()->create(['name' => 'Bennani Conseil']);
    issuedInvoice($client, '2026-10-01', '2026-10-31');

    $this->get(route('clients.show', $client))->assertSee('Relevé de compte (PDF)');

    $response = $this->get(route('clients.statement', $client))->assertOk();
    expect($response->headers->get('Content-Type'))->toBe('application/pdf')
        ->and($response->headers->get('Content-Disposition'))->toContain('Releve_bennani_conseil_2026-10-29.pdf')
        ->and(substr($response->getContent(), 0, 4))->toBe('%PDF');
});

// ---------- Taille du texte ----------

test('chaque utilisateur choisit la taille du texte', function () {
    $this->get('/tableau-de-bord')->assertSee('class="text-size-normal"', false);

    $this->patch(route('profile.text-size'), ['text_size' => 'xlarge'])->assertRedirect(route('profile.edit'));

    expect($this->user->fresh()->text_size)->toBe('xlarge');
    $this->get('/tableau-de-bord')->assertSee('class="text-size-xlarge"', false);
    $this->get('/profil')->assertSee('Taille du texte')->assertSee('Très grand');
});

test('une taille de texte inconnue est refusée', function () {
    $this->patch(route('profile.text-size'), ['text_size' => 'enorme'])->assertSessionHasErrors('text_size');

    expect($this->user->fresh()->text_size)->toBe('normal');
});

// ---------- Installation sur le téléphone ----------

test('l’application peut s’installer sur l’écran d’accueil', function () {
    $manifest = json_decode(File::get(public_path('manifest.webmanifest')), true);

    expect($manifest['display'])->toBe('standalone')
        ->and($manifest['start_url'])->toBe('/tableau-de-bord');
    foreach ($manifest['icons'] as $icon) {
        expect(File::exists(public_path(ltrim($icon['src'], '/'))))->toBeTrue();
    }

    $this->get('/tableau-de-bord')->assertSee('rel="manifest"', false)->assertSee('apple-touch-icon', false);
    auth()->logout();
    $this->get('/connexion')->assertSee('rel="manifest"', false);
});
