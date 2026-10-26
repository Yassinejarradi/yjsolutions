<?php

use App\Models\Setting;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

function documentData(array $overrides = []): array
{
    return array_merge([
        'invoice_prefix' => 'FAC',
        'quote_prefix' => 'DEV',
        'default_vat_rate' => 20,
        'payment_terms_days' => 30,
        'quote_validity_days' => 30,
        'quote_payment_terms' => '50 % à la commande, solde à la livraison',
        'invoice_notes' => null,
        'quote_notes' => null,
    ], $overrides);
}

test('la page devis et factures s’affiche', function () {
    $this->get('/parametres/documents')->assertOk()->assertSee('Numérotation');
});

test('les paramètres des documents peuvent être modifiés', function () {
    $this->put('/parametres/documents', documentData([
        'invoice_prefix' => 'fa',
        'default_vat_rate' => 14,
        'payment_terms_days' => 60,
        'invoice_notes' => 'Pénalités de retard : 1 % par mois.',
    ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect('/parametres/documents');

    expect(Setting::current())
        ->invoice_prefix->toBe('FA')
        ->default_vat_rate->toBe(14)
        ->payment_terms_days->toBe(60)
        ->invoice_notes->toBe('Pénalités de retard : 1 % par mois.');
});

test('seuls les taux de TVA marocains sont acceptés', function () {
    $this->put('/parametres/documents', documentData(['default_vat_rate' => 19]))
        ->assertSessionHasErrors('default_vat_rate');
});

test('les préfixes des factures et des devis doivent être différents', function () {
    $this->put('/parametres/documents', documentData(['invoice_prefix' => 'DOC', 'quote_prefix' => 'doc']))
        ->assertSessionHasErrors('invoice_prefix');
});

test('un préfixe ne contient que des lettres', function () {
    $this->put('/parametres/documents', documentData(['invoice_prefix' => 'FAC-']))
        ->assertSessionHasErrors('invoice_prefix');
});

test('les paramètres créés à la volée ont leurs valeurs par défaut', function () {
    Setting::query()->delete();

    $setting = Setting::current();

    expect($setting->payment_terms_days)->toBe(30)
        ->and($setting->default_vat_rate)->toBe(20)
        ->and($setting->invoice_prefix)->toBe('FAC');
});
