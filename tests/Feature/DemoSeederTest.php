<?php

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Quote;
use App\Models\User;
use Database\Seeders\DemoSeeder;

test('les données de démonstration couvrent les 12 derniers mois', function () {
    $this->seed(DemoSeeder::class);

    expect(User::where('email', 'demo@jysolutions.ma')->exists())->toBeTrue()
        ->and(Client::count())->toBe(6)
        ->and(Invoice::query()->billed()->count())->toBeGreaterThan(10)
        ->and(Invoice::query()->where('status', 'cancelled')->count())->toBe(1)
        ->and(Invoice::query()->where('status', 'draft')->count())->toBe(1)
        ->and(Quote::count())->toBe(7)
        ->and(Invoice::query()->billed()->whereColumn('amount_paid', '>', 'total_ttc')->exists())->toBeFalse();

    // Numéros sans trou, dans l'ordre des dates.
    $numbers = Invoice::query()->whereNotNull('number')->orderBy('issue_date')->orderBy('id')->pluck('number');
    expect($numbers->all())->toBe($numbers->sort()->values()->all());

    $this->actingAs(User::first())->get('/tableau-de-bord')->assertOk();
});

test('les données de démonstration refusent une base déjà utilisée', function () {
    Invoice::factory()->issued()->create();

    $this->seed(DemoSeeder::class);
})->throws(RuntimeException::class, 'base vide');
