<?php

use App\Livewire\ClientList;
use App\Models\Client;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());

    Client::factory()->create(['name' => 'Bennani Conseil', 'city' => 'Rabat', 'ice' => '001111111000011']);
    Client::factory()->create(['name' => 'Atlas Industrie', 'city' => 'Casablanca', 'contact_name' => 'Mme Alaoui']);
    Client::factory()->archived()->create(['name' => 'Ancien Client']);
});

test('la liste affiche les clients actifs triés par nom', function () {
    Livewire::test(ClientList::class)
        ->assertSeeInOrder(['Atlas Industrie', 'Bennani Conseil'])
        ->assertDontSee('Ancien Client');
});

test('le tri par nom peut être inversé', function () {
    Livewire::test(ClientList::class)
        ->call('toggleSort')
        ->assertSet('sort', 'desc')
        ->assertSeeInOrder(['Bennani Conseil', 'Atlas Industrie'])
        ->assertSee('Z → A');
});

test('le filtre Archivés n’affiche que les clients archivés', function () {
    Livewire::test(ClientList::class)
        ->set('status', 'archives')
        ->assertSee('Ancien Client')
        ->assertDontSee('Atlas Industrie')
        ->assertDontSee('Bennani Conseil');
});

test('la recherche porte sur le nom, la ville, l’ICE et le contact', function (string $term, string $found, string $hidden) {
    Livewire::test(ClientList::class)
        ->set('search', $term)
        ->assertSee($found)
        ->assertDontSee($hidden);
})->with([
    'nom' => ['benn', 'Bennani Conseil', 'Atlas Industrie'],
    'ville' => ['Casablanca', 'Atlas Industrie', 'Bennani Conseil'],
    'ICE' => ['001111111', 'Bennani Conseil', 'Atlas Industrie'],
    'contact' => ['Alaoui', 'Atlas Industrie', 'Bennani Conseil'],
]);

test('une recherche sans résultat le dit clairement', function () {
    Livewire::test(ClientList::class)
        ->set('search', 'zzz')
        ->assertSee('Aucun client ne correspond à « zzz ».');
});

test('la recherche et le filtre sont gardés dans l’adresse', function () {
    $this->get('/clients?statut=archives')->assertOk()->assertSee('Ancien Client')->assertDontSee('Atlas Industrie');
    $this->get('/clients?recherche=Rabat')->assertOk()->assertSee('Bennani Conseil')->assertDontSee('Atlas Industrie');
});
