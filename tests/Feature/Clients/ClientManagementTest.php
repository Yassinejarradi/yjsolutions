<?php

use App\Models\Client;
use App\Models\Setting;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

function clientPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Atlas Conseil SARL',
        'ice' => '001234567000089',
        'if_number' => '12345678',
        'rc' => '456789',
        'address' => '12 rue Moulay Youssef',
        'city' => 'Rabat',
        'phone' => '0537 12 34 56',
        'email' => 'contact@atlas.ma',
        'contact_name' => 'M. Bennani',
        'payment_terms_days' => '',
        'notes' => '',
    ], $overrides);
}

test('les pages clients demandent d’être connecté', function () {
    auth()->logout();

    $this->get('/clients')->assertRedirect('/connexion');
    $this->get('/clients/nouveau')->assertRedirect('/connexion');
});

test('la liste des clients s’affiche', function () {
    Client::factory()->create(['name' => 'Atlas Conseil']);

    $this->get('/clients')->assertOk()->assertSee('+ Nouveau client')->assertSee('Atlas Conseil');
});

test('un client peut être créé', function () {
    $this->get('/clients/nouveau')->assertOk();

    $response = $this->post('/clients', clientPayload(['ice' => '001 234 567 000 089']));

    $client = Client::where('name', 'Atlas Conseil SARL')->firstOrFail();
    $response->assertRedirect("/clients/{$client->id}")->assertSessionHas('success');

    expect($client->ice)->toBe('001234567000089')
        ->and($client->city)->toBe('Rabat')
        ->and($client->payment_terms_days)->toBeNull()
        ->and($client->notes)->toBeNull()
        ->and($client->isArchived())->toBeFalse();
});

test('seule la raison sociale est obligatoire', function () {
    $this->post('/clients', ['name' => 'Client minimal'])->assertSessionHasNoErrors();

    $client = Client::where('name', 'Client minimal')->firstOrFail();
    expect($client->ice)->toBeNull()->and($client->address)->toBe('');

    $this->post('/clients', clientPayload(['name' => '']))
        ->assertSessionHasErrors(['name' => 'Le champ raison sociale est obligatoire.']);
});

test('l’ICE du client doit contenir 15 chiffres', function () {
    $this->post('/clients', clientPayload(['ice' => '12345']))->assertSessionHasErrors('ice');
});

test('deux clients ne peuvent pas avoir le même ICE', function () {
    Client::factory()->create(['ice' => '001234567000089']);

    $this->post('/clients', clientPayload())
        ->assertSessionHasErrors(['ice' => 'Un autre client a déjà cet ICE.']);
});

test('l’email du client doit être valide', function () {
    $this->post('/clients', clientPayload(['email' => 'pas-un-email']))->assertSessionHasErrors('email');
});

test('un client peut être modifié en gardant son ICE', function () {
    $client = Client::factory()->create(['ice' => '001234567000089']);

    $this->get("/clients/{$client->id}/modifier")->assertOk();

    $this->put("/clients/{$client->id}", clientPayload(['name' => 'Nouveau nom', 'phone' => '']))
        ->assertSessionHasNoErrors()
        ->assertRedirect("/clients/{$client->id}");

    $client->refresh();
    expect($client->name)->toBe('Nouveau nom')->and($client->phone)->toBe('');
});

test('le délai de paiement du client remplace celui des paramètres', function () {
    Setting::current()->update(['payment_terms_days' => 30]);

    $sansDelai = Client::factory()->create(['payment_terms_days' => null]);
    $avecDelai = Client::factory()->create(['payment_terms_days' => 60]);
    $comptant = Client::factory()->create(['payment_terms_days' => 0]);

    expect($sansDelai->effectivePaymentTermsDays())->toBe(30)
        ->and($avecDelai->effectivePaymentTermsDays())->toBe(60)
        ->and($comptant->effectivePaymentTermsDays())->toBe(0);

    $this->get("/clients/{$sansDelai->id}")->assertSee('30 jours')->assertSee('(délai des Paramètres)');
});

test('le délai de paiement est limité à 365 jours', function () {
    $this->post('/clients', clientPayload(['payment_terms_days' => '400']))->assertSessionHasErrors('payment_terms_days');
});

test('la fiche client affiche ses coordonnées et la place des documents', function () {
    $client = Client::factory()->create(['name' => 'Atlas Conseil', 'address' => '12 rue Moulay Youssef', 'city' => 'Rabat']);

    $this->get("/clients/{$client->id}")
        ->assertOk()
        ->assertSee('12 rue Moulay Youssef – Rabat')
        ->assertSee('Total facturé')
        ->assertSee('Reste à payer')
        ->assertSee('0,00 MAD')
        ->assertSee('Aucun devis pour ce client.')
        ->assertSee('Aucune facture pour ce client.');
});

test('un client peut être archivé puis réactivé', function () {
    $client = Client::factory()->create();

    $this->patch("/clients/{$client->id}/archiver")->assertRedirect("/clients/{$client->id}")->assertSessionHas('success');
    expect($client->refresh()->isArchived())->toBeTrue();

    $this->get("/clients/{$client->id}")->assertOk()->assertSee('Archivé')->assertSee('Réactiver le client');

    $this->patch("/clients/{$client->id}/reactiver")->assertRedirect("/clients/{$client->id}");
    expect($client->refresh()->isArchived())->toBeFalse();
});

test('un client sans document peut être supprimé', function () {
    $client = Client::factory()->create();

    $this->get("/clients/{$client->id}")->assertSee('Supprimer le client');

    $this->delete("/clients/{$client->id}")->assertRedirect('/clients')->assertSessionHas('success');

    expect(Client::find($client->id))->toBeNull();
});

test('un client archivé n’est plus proposé pour un nouveau document', function () {
    $actif = Client::factory()->create(['name' => 'Actif']);
    $archive = Client::factory()->archived()->create(['name' => 'Archivé']);

    expect(Client::selectable()->pluck('name')->all())->toBe(['Actif']);

    // Sur un document existant, son client reste proposé même archivé.
    expect(Client::selectable($archive->id)->pluck('name')->all())->toBe(['Actif', 'Archivé']);

    // Et sa fiche reste accessible.
    $this->get("/clients/{$archive->id}")->assertOk();
});
