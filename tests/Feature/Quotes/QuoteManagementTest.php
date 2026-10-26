<?php

use App\Enums\QuoteStatus;
use App\Livewire\QuoteList;
use App\Models\Client;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

beforeEach(function () {
    Carbon::setTestNow('2026-10-26 10:00:00');
    $this->actingAs(User::factory()->create());
});

test('les pages des devis demandent d’être connecté', function () {
    auth()->logout();

    $this->get('/devis')->assertRedirect('/connexion');
    $this->get('/devis/nouveau')->assertRedirect('/connexion');
});

test('la liste des devis s’affiche avec les brouillons et les devis envoyés', function () {
    Quote::factory()->withLines()->create(['client_id' => Client::factory()->create(['name' => 'Client Brouillon'])]);
    Quote::factory()->sent()->create(['client_id' => Client::factory()->create(['name' => 'Client Envoyé'])]);

    $this->get('/devis')->assertOk()
        ->assertSee('+ Nouveau devis')
        ->assertSee('Client Brouillon')
        ->assertSee('Client Envoyé')
        ->assertSee('DEV2026-001');
});

test('« Expiré » est calculé après la date de validité', function () {
    $quote = Quote::factory()->sent()->create(['issue_date' => '2026-09-01', 'valid_until' => '2026-10-01']);

    expect($quote->fresh()->status)->toBe(QuoteStatus::Sent)
        ->and($quote->fresh()->displayStatus())->toBe(QuoteStatus::Expired);

    Carbon::setTestNow('2026-09-15');
    expect($quote->fresh()->displayStatus())->toBe(QuoteStatus::Sent);
});

/**
 * Identifiants des devis affichés par la liste avec ces filtres.
 */
function listedQuotes(array $filters): array
{
    $component = Livewire::test(QuoteList::class);

    foreach ($filters as $property => $value) {
        $component->set($property, $value);
    }

    return $component->viewData('quotes')->pluck('id')->sort()->values()->all();
}

test('la liste filtre par statut, y compris Expiré', function () {
    $draft = Quote::factory()->withLines()->create();
    $sent = Quote::factory()->sent()->create();
    $expired = Quote::factory()->sent()->create(['issue_date' => '2026-08-01', 'valid_until' => '2026-08-31']);
    $accepted = Quote::factory()->sent()->create();
    $accepted->fresh()->accept();

    expect(listedQuotes(['status' => 'draft']))->toBe([$draft->id])
        ->and(listedQuotes(['status' => 'sent']))->toBe([$sent->id])
        ->and(listedQuotes(['status' => 'expired']))->toBe([$expired->id])
        ->and(listedQuotes(['status' => 'accepted']))->toBe([$accepted->id])
        ->and(listedQuotes([]))->toHaveCount(4);
});

test('la liste filtre par client, période et recherche', function () {
    $atlas = Client::factory()->create(['name' => 'Atlas']);
    $boreal = Client::factory()->create(['name' => 'Boréal']);
    $october = Quote::factory()->withLines()->create(['client_id' => $atlas->id, 'issue_date' => '2026-10-05', 'reference' => 'REF-OCT']);
    $september = Quote::factory()->withLines()->create(['client_id' => $boreal->id, 'issue_date' => '2026-09-10', 'reference' => 'REF-SEPT']);
    $lastYear = Quote::factory()->withLines()->create(['client_id' => $boreal->id, 'issue_date' => '2025-06-01', 'reference' => 'REF-2025']);

    expect(listedQuotes(['clientId' => (string) $atlas->id]))->toBe([$october->id])
        ->and(listedQuotes(['period' => 'mois']))->toBe([$october->id])
        ->and(listedQuotes(['period' => 'mois-dernier']))->toBe([$september->id])
        ->and(listedQuotes(['period' => 'annee']))->toBe([$october->id, $september->id])
        ->and(listedQuotes(['period' => 'annee-derniere']))->toBe([$lastYear->id])
        ->and(listedQuotes(['period' => 'dates', 'from' => '01/09/2026', 'to' => '30/09/2026']))->toBe([$september->id])
        ->and(listedQuotes(['search' => 'REF-SEPT']))->toBe([$september->id])
        ->and(listedQuotes(['search' => 'bor']))->toBe([$september->id, $lastYear->id]);
});

test('la liste affiche le nombre de devis et leur total', function () {
    Quote::factory()->withLines([['Conseil', '1', 100000]])->create();
    Quote::factory()->withLines([['Conseil', '1', 50000]])->create();

    Livewire::test(QuoteList::class)->assertSee('2 devis · total 1 800,00 MAD TTC');
});

test('un brouillon s’ouvre dans l’éditeur, un devis envoyé en lecture seule', function () {
    $draft = Quote::factory()->withLines()->create();
    $sent = Quote::factory()->sent()->create();

    $this->get("/devis/{$draft->id}")->assertOk()->assertSee('+ Ajouter une ligne')->assertSee('Marquer comme envoyé');
    $this->get("/devis/{$sent->id}")->assertOk()
        ->assertSee('Devis DEV2026-001')
        ->assertSee('Voir le PDF')
        ->assertSee('Le client a accepté')
        ->assertDontSee('Marquer comme envoyé');
});

test('dupliquer un devis envoyé crée un brouillon sans numéro, daté d’aujourd’hui', function () {
    $quote = Quote::factory()->withLines([['Conseil', '2', 50000], ['Suivi', '1', 10000]])
        ->create(['issue_date' => '2026-01-10', 'valid_until' => '2026-02-09', 'discount_percent' => '5']);
    $quote->send();

    $this->post("/devis/{$quote->id}/dupliquer")->assertRedirect()->assertSessionHas('success');

    $copy = Quote::latest('id')->first();
    expect($copy->id)->not->toBe($quote->id)
        ->and($copy->number)->toBeNull()
        ->and($copy->isDraft())->toBeTrue()
        ->and($copy->issue_date->format('d/m/Y'))->toBe('26/10/2026')
        ->and($copy->client_id)->toBe($quote->client_id)
        ->and($copy->lines->pluck('description')->all())->toBe(['Conseil', 'Suivi'])
        ->and($copy->total_ttc)->toBe($quote->fresh()->total_ttc);
});

test('un devis dupliqué ne reprend pas un client archivé', function () {
    $quote = Quote::factory()->sent()->create();
    $quote->client->archive();

    expect($quote->fresh()->duplicate()->client_id)->toBeNull();
});

test('un devis envoyé peut être accepté ou refusé, une seule fois', function () {
    $accepted = Quote::factory()->sent()->create();
    $refused = Quote::factory()->sent()->create();

    $this->patch("/devis/{$accepted->id}/accepter")->assertSessionHas('success');
    $this->patch("/devis/{$refused->id}/refuser")->assertSessionHas('success');

    expect($accepted->fresh()->status)->toBe(QuoteStatus::Accepted)
        ->and($refused->fresh()->status)->toBe(QuoteStatus::Refused);

    $this->patch("/devis/{$accepted->id}/refuser")->assertSessionHas('error');
    expect($accepted->fresh()->status)->toBe(QuoteStatus::Accepted);
});

test('un brouillon ne peut pas être accepté', function () {
    $draft = Quote::factory()->withLines()->create();

    $this->patch("/devis/{$draft->id}/accepter")->assertSessionHas('error');
    expect($draft->fresh()->status)->toBe(QuoteStatus::Draft);
});

test('seul un brouillon peut être supprimé', function () {
    $draft = Quote::factory()->withLines()->create();
    $sent = Quote::factory()->sent()->create();

    $this->delete("/devis/{$draft->id}")->assertRedirect('/devis');
    expect(Quote::find($draft->id))->toBeNull();

    $this->delete("/devis/{$sent->id}")->assertSessionHas('error');
    expect(Quote::find($sent->id))->not->toBeNull();
});

test('un client qui a un devis ne peut pas être supprimé, seulement archivé', function () {
    $quote = Quote::factory()->withLines()->create();
    $client = $quote->client;

    expect($client->hasDocuments())->toBeTrue();

    $this->get("/clients/{$client->id}")->assertDontSee('Supprimer le client')->assertSee('seulement archivé');
    $this->delete("/clients/{$client->id}")->assertSessionHas('error');
    expect(Client::find($client->id))->not->toBeNull();
});

test('la fiche client liste ses devis', function () {
    $quote = Quote::factory()->sent()->create();

    $this->get("/clients/{$quote->client_id}")
        ->assertOk()
        ->assertSee('DEV2026-001')
        ->assertSee('Envoyé')
        ->assertSee('+ Nouveau devis')
        ->assertDontSee('Aucun devis pour ce client.');
});

test('le délai de réalisation par défaut se règle dans les Paramètres', function () {
    $this->get('/parametres/documents')->assertOk()->assertSee('Délai de réalisation des devis');

    $this->put('/parametres/documents', [
        'invoice_prefix' => 'FAC', 'quote_prefix' => 'DEV', 'default_vat_rate' => 20,
        'payment_terms_days' => 30, 'quote_validity_days' => 30,
        'quote_payment_terms' => '', 'quote_completion_delay' => '4 semaines',
    ])->assertSessionHasNoErrors();

    expect(Quote::newDraft()->completion_delay)->toBe('4 semaines');
});
