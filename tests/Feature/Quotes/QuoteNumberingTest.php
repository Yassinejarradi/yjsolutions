<?php

use App\Enums\QuoteStatus;
use App\Models\Client;
use App\Models\Quote;
use App\Models\Sequence;
use App\Models\Setting;
use Illuminate\Support\Carbon;

beforeEach(function () {
    Carbon::setTestNow('2026-10-26 10:00:00');
});

test('un brouillon n’a pas de numéro', function () {
    $quote = Quote::factory()->withLines()->create();

    expect($quote->number)->toBeNull()
        ->and($quote->status)->toBe(QuoteStatus::Draft)
        ->and(Sequence::count())->toBe(0);
});

test('le numéro est attribué à l’envoi, sans trou et dans l’ordre d’envoi', function () {
    $first = Quote::factory()->withLines()->create();
    $second = Quote::factory()->withLines()->create();
    $third = Quote::factory()->withLines()->create();

    // Le brouillon créé en dernier est envoyé en premier : c'est l'ordre d'envoi qui compte.
    $third->send();
    $first->send();

    expect($third->fresh()->number)->toBe('DEV2026-001')
        ->and($first->fresh()->number)->toBe('DEV2026-002')
        ->and($second->fresh()->number)->toBeNull();

    $second->send();
    expect($second->fresh()->number)->toBe('DEV2026-003');
});

test('la numérotation repart à 001 chaque année, selon la date du devis', function () {
    Quote::factory()->withLines()->create()->send();
    $lastYear = Quote::factory()->withLines()->create(['issue_date' => '2025-12-30', 'valid_until' => '2026-01-29']);
    $lastYear->send();

    expect($lastYear->fresh()->number)->toBe('DEV2025-001');

    $next = Quote::factory()->withLines()->create();
    $next->send();
    expect($next->fresh()->number)->toBe('DEV2026-002');
});

test('le préfixe vient des Paramètres', function () {
    Setting::current()->update(['quote_prefix' => 'OFF']);

    $quote = Quote::factory()->withLines()->create();
    $quote->send();

    expect($quote->fresh()->number)->toBe('OFF2026-001');
});

test('un envoi refusé ne consomme pas de numéro', function () {
    $sansClient = Quote::factory()->withLines()->create(['client_id' => null]);

    expect(fn () => $sansClient->send())->toThrow(LogicException::class);
    expect($sansClient->fresh()->number)->toBeNull()
        ->and(Sequence::query()->value('last_number'))->toBeNull();

    $ok = Quote::factory()->withLines()->create();
    $ok->send();
    expect($ok->fresh()->number)->toBe('DEV2026-001');
});

test('un devis ne peut pas être numéroté deux fois, même depuis une copie périmée', function () {
    $quote = Quote::factory()->withLines()->create();
    $stale = Quote::find($quote->id); // chargé avant l'envoi, comme dans un deuxième onglet

    $quote->send();

    expect(fn () => $stale->send())->toThrow(LogicException::class, 'Seul un brouillon peut être envoyé.');
    expect(Sequence::query()->value('last_number'))->toBe(1)
        ->and($quote->fresh()->number)->toBe('DEV2026-001');
});

test('le compteur est créé au besoin puis incrémenté', function () {
    expect(Sequence::next('quote', 2026))->toBe(1)
        ->and(Sequence::next('quote', 2026))->toBe(2)
        ->and(Sequence::next('quote', 2027))->toBe(1)
        ->and(Sequence::next('invoice', 2026))->toBe(1)
        ->and(Sequence::count())->toBe(3);
});

test('l’envoi copie le client et la société, et retire les lignes vides', function () {
    Setting::current()->update(['company_name' => 'JY SOLUTIONS']);
    $client = Client::factory()->create(['name' => 'Atlas SA', 'address' => '12 rue X', 'city' => 'Rabat', 'ice' => '001234567000089']);
    $quote = Quote::factory()->create(['client_id' => $client->id]);
    $quote->replaceLines([
        ['description' => 'Conseil', 'quantity' => '1', 'unit_price' => 100000],
        ['description' => '', 'quantity' => '1', 'unit_price' => null],
    ]);

    $quote->send();
    $client->update(['name' => 'Atlas Groupe', 'address' => 'Nouvelle adresse']);
    Setting::current()->update(['company_name' => 'Autre nom']);

    $quote = $quote->fresh();
    expect($quote->clientDetails())->toMatchArray(['name' => 'Atlas SA', 'address' => '12 rue X – Rabat', 'ice' => '001234567000089'])
        ->and($quote->companyDetails()['name'])->toBe('JY SOLUTIONS')
        ->and($quote->lines)->toHaveCount(1);
});
