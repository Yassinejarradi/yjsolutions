<?php

use App\Enums\QuoteStatus;
use App\Livewire\QuoteEditor;
use App\Models\Client;
use App\Models\Quote;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

beforeEach(function () {
    Carbon::setTestNow('2026-10-26 14:32:00');
    $this->actingAs(User::factory()->create());

    Setting::current()->update([
        'default_vat_rate' => 20,
        'quote_validity_days' => 30,
        'quote_payment_terms' => '50 % à la commande',
        'quote_completion_delay' => '3 semaines',
    ]);
});

/** Ligne de l'éditeur telle que saisie à l'écran. */
function editorLine(string $description, string $quantity, string $price): array
{
    return ['key' => uniqid(), 'description' => $description, 'quantity' => $quantity, 'unit_price' => $price];
}

test('un nouveau devis reprend les valeurs des Paramètres', function () {
    Livewire::test(QuoteEditor::class)
        ->assertSet('issueDate', '26/10/2026')
        ->assertSet('validUntil', '25/11/2026')
        ->assertSet('vatRate', '20')
        ->assertSet('paymentTerms', '50 % à la commande')
        ->assertSet('completionDelay', '3 semaines')
        ->assertSee('30 jours, jusqu’au 25/11/2026')
        ->assertSee('Délai de réalisation');

    expect(Quote::count())->toBe(0); // rien n'est créé avant la première saisie
});

test('la page Nouveau devis s’affiche', function () {
    $this->get('/devis/nouveau')->assertOk()->assertSee('Nouveau devis')->assertSee('+ Ajouter une ligne');
});

test('le premier enregistrement crée le brouillon avec ses lignes et ses totaux', function () {
    $client = Client::factory()->create();

    $component = Livewire::test(QuoteEditor::class)
        ->set('clientId', $client->id)
        ->set('lines', [editorLine("Audit\nRapport final", '2,5', '1 250,50'), editorLine('Formation', '1', '800')])
        ->set('discountPercent', '10')
        ->call('save')
        ->assertReturned(true)
        ->assertSet('savedAt', '14:32')
        ->assertSee('Enregistré à 14:32');

    $quote = Quote::sole();
    expect($component->get('quoteId'))->toBe($quote->id)
        ->and($quote->status)->toBe(QuoteStatus::Draft)
        ->and($quote->client_id)->toBe($client->id)
        ->and($quote->lines->pluck('description')->all())->toBe(["Audit\nRapport final", 'Formation'])
        ->and($quote->lines[0]->quantity)->toBe('2.500')
        ->and($quote->lines[0]->unit_price)->toBe(125050)
        ->and($quote->lines[0]->amount)->toBe(312625)   // 2,5 × 1 250,50 = 3 126,25
        ->and($quote->subtotal)->toBe(392625)
        ->and($quote->discount)->toBe(39263)            // 10 % de 3 926,25 = 392,625 → 392,63
        ->and($quote->total_ht)->toBe(353362)
        ->and($quote->vat)->toBe(70672)
        ->and($quote->total_ttc)->toBe(424034);
});

test('un brouillon rouvert garde ses saisies', function () {
    $quote = Quote::factory()->withLines([['Conseil', '1.5', 120000]])->create(['discount_percent' => '12.5', 'reference' => 'BC-77']);

    Livewire::test(QuoteEditor::class, ['quote' => $quote])
        ->assertSet('quoteId', $quote->id)
        ->assertSet('reference', 'BC-77')
        ->assertSet('discountPercent', '12,5')
        ->assertSet('lines.0.description', 'Conseil')
        ->assertSet('lines.0.quantity', '1,5')
        ->assertSet('lines.0.unit_price', '1 200,00');
});

test('les montants s’affichent en direct pendant la saisie', function () {
    Livewire::test(QuoteEditor::class)
        ->set('lines', [editorLine('Conseil', '3', '1000')])
        ->assertSee('3 000,00')
        ->assertSee('3 600,00 MAD')
        ->assertSee('Trois mille six cents dirhams TTC')
        ->set('vatRate', '0')
        ->assertSee('3 000,00 MAD')
        ->assertSee('Trois mille dirhams TTC');
});

test('ajouter, dupliquer, déplacer et supprimer des lignes', function () {
    $component = Livewire::test(QuoteEditor::class)
        ->set('lines', [editorLine('A', '1', '10'), editorLine('B', '1', '20')]);

    $keys = fn () => array_column($component->get('lines'), 'description');

    $component->call('addLine')->assertDispatched('focus-line')->assertDispatched('document-changed');
    expect($keys())->toBe(['A', 'B', '']);

    $component->call('duplicateLine', $component->get('lines')[0]['key']);
    expect($keys())->toBe(['A', 'A', 'B', '']);

    $component->call('moveLine', $component->get('lines')[2]['key'], -1);
    expect($keys())->toBe(['A', 'B', 'A', '']);

    // Glisser-déposer : la ligne B tout en bas.
    $component->call('sortLine', $component->get('lines')[1]['key'], 3);
    expect($keys())->toBe(['A', 'A', '', 'B']);

    $component->call('removeLine', $component->get('lines')[3]['key'])->assertSee('Ligne supprimée.')->assertSee('Annuler');
    expect($keys())->toBe(['A', 'A', '']);

    $component->call('undoRemove');
    expect($keys())->toBe(['A', 'A', '', 'B'])
        ->and($component->get('removedLine'))->toBeNull();
});

test('supprimer la dernière ligne laisse une ligne vide, et Annuler la rétablit', function () {
    $component = Livewire::test(QuoteEditor::class)->set('lines', [editorLine('Seule', '1', '10')]);

    $component->call('removeLine', $component->get('lines')[0]['key']);
    expect(array_column($component->get('lines'), 'description'))->toBe(['']);

    $component->call('undoRemove');
    expect(array_column($component->get('lines'), 'description'))->toBe(['Seule']);
});

test('une saisie invalide n’est pas enregistrée et est signalée', function () {
    Livewire::test(QuoteEditor::class)
        ->set('lines', [editorLine('Conseil', 'deux', '1000')])
        ->call('save')
        ->assertReturned(false)
        ->assertHasErrors(['lines.0.quantity'])
        ->assertSee('Quantité invalide.');

    Livewire::test(QuoteEditor::class)
        ->set('issueDate', '31/02/2026')
        ->call('save')
        ->assertHasErrors(['issueDate']);

    Livewire::test(QuoteEditor::class)
        ->set('validUntil', '01/10/2026')
        ->call('save')
        ->assertHasErrors(['validUntil' => 'Ne peut pas être avant la date du devis.']);

    Livewire::test(QuoteEditor::class)
        ->set('discountPercent', '120')
        ->call('save')
        ->assertHasErrors(['discountPercent']);

    expect(Quote::count())->toBe(0);
});

test('changer la date du devis garde la même durée de validité', function () {
    Livewire::test(QuoteEditor::class)
        ->set('issueDate', '01/11/2026')
        ->assertSet('validUntil', '01/12/2026');
});

test('l’auto-complétion propose les lignes des autres documents avec leur prix', function () {
    Quote::factory()->withLines([['Audit organisationnel', '1', 450000], ['Formation Excel', '1', 90000]])->create();
    Quote::factory()->withLines([['Audit organisationnel', '1', 500000]])->create(); // plus récent : ce prix l'emporte

    $suggestions = Livewire::test(QuoteEditor::class)->instance()->suggest('audit');

    expect($suggestions)->toHaveCount(1)
        ->and($suggestions[0])->toBe(['description' => 'Audit organisationnel', 'unit_price' => '5 000,00', 'price_label' => '5 000,00 MAD']);

    expect(Livewire::test(QuoteEditor::class)->instance()->suggest('a'))->toBe([]);
});

test('l’auto-complétion ne propose pas les lignes du devis en cours', function () {
    $quote = Quote::factory()->withLines([['Mission propre au devis', '1', 1000]])->create();

    expect(Livewire::test(QuoteEditor::class, ['quote' => $quote])->instance()->suggest('mission'))->toBe([]);
});

test('le choix du client ne propose que les clients actifs', function () {
    Client::factory()->create(['name' => 'Client actif']);
    $archived = Client::factory()->archived()->create(['name' => 'Client archivé']);

    Livewire::test(QuoteEditor::class)
        ->assertSee('Client actif')
        ->assertDontSee('Client archivé')
        ->call('chooseClient', $archived->id)
        ->assertSet('clientId', null);
});

test('un brouillon dont le client a été archivé le garde', function () {
    $client = Client::factory()->create(['name' => 'Ancien client']);
    $quote = Quote::factory()->withLines()->create(['client_id' => $client->id]);
    $client->archive();

    Livewire::test(QuoteEditor::class, ['quote' => $quote])
        ->assertSet('clientId', $client->id)
        ->assertSee('Ancien client');
});

test('un client peut être créé sans quitter le devis', function () {
    Livewire::test(QuoteEditor::class)
        ->call('openClientForm')
        ->assertSet('showClientForm', true)
        ->set('newClient.name', '')
        ->call('createClient')
        ->assertHasErrors(['newClient.name' => 'required'])
        ->set('newClient', ['name' => 'Nouveau Client SARL', 'ice' => '001 234 567 000 089', 'address' => '1 rue A', 'city' => 'Fès'])
        ->call('createClient')
        ->assertHasNoErrors()
        ->assertSet('showClientForm', false)
        ->assertSet('clientId', Client::where('name', 'Nouveau Client SARL')->value('id'))
        ->assertSee('1 rue A – Fès');

    expect(Client::sole()->ice)->toBe('001234567000089');
});

test('un devis sans client ou sans ligne ne peut pas être envoyé', function () {
    Livewire::test(QuoteEditor::class)
        ->set('lines', [editorLine('Conseil', '1', '1000')])
        ->call('send')
        ->assertHasErrors(['clientId'])
        ->assertSee('Choisissez le client avant d’envoyer le devis.');

    Livewire::test(QuoteEditor::class)
        ->set('clientId', Client::factory()->create()->id)
        ->call('send')
        ->assertHasErrors(['lines']);

    expect(Quote::whereNotNull('number')->count())->toBe(0);
});

test('envoyer un devis lui attribue son numéro et ouvre sa page', function () {
    $component = Livewire::test(QuoteEditor::class)
        ->set('clientId', Client::factory()->create()->id)
        ->set('lines', [editorLine('Conseil', '1', '1000'), editorLine('', '1', '')])
        ->call('send');

    $quote = Quote::sole();
    $component->assertRedirect(route('quotes.show', $quote));

    expect($quote->number)->toBe('DEV2026-001')
        ->and($quote->status)->toBe(QuoteStatus::Sent)
        ->and($quote->lines)->toHaveCount(1); // la ligne vide est retirée
});

test('un devis envoyé n’est plus modifiable', function () {
    $quote = Quote::factory()->sent()->create();

    Livewire::test(QuoteEditor::class, ['quote' => $quote])->assertForbidden();

    $this->get("/devis/{$quote->id}")->assertOk()->assertDontSee('+ Ajouter une ligne')->assertSee('n’est plus modifiable');
});

test('un nouveau devis ouvert depuis la fiche client est préréglé sur ce client', function () {
    $client = Client::factory()->create(['name' => 'Client préréglé']);

    $this->get("/devis/nouveau?client={$client->id}")->assertOk()->assertSee('Client préréglé');
});

test('l’ICE manquant du client se complète depuis la feuille', function () {
    $client = Client::factory()->create(['name' => 'Sans ICE SARL', 'ice' => null]);
    Client::factory()->create(['ice' => '001234567000089']);

    $editor = Livewire::test(QuoteEditor::class)
        ->call('chooseClient', $client->id)
        ->assertSee('L’ICE de ce client n’est pas renseigné.')
        ->set('clientIce', '123')
        ->call('saveClientIce')
        ->assertHasErrors(['clientIce' => 'digits'])
        ->set('clientIce', '001234567000089')
        ->call('saveClientIce')
        ->assertSee('Un autre client a déjà cet ICE.')
        ->set('clientIce', '002 345 678 000 091')
        ->call('saveClientIce')
        ->assertHasNoErrors()
        ->assertDontSee('L’ICE de ce client n’est pas renseigné.')
        ->assertSee('ICE : 002345678000091');

    expect($client->fresh()->ice)->toBe('002345678000091');
});
