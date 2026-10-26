<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('user:create crée un utilisateur', function () {
    $this->artisan('user:create', ['--name' => 'Jamal', '--email' => 'Jamal@Example.com'])
        ->expectsQuestion('Mot de passe (8 caractères minimum)', 'motdepasse')
        ->expectsOutputToContain('Utilisateur créé')
        ->assertSuccessful();

    $user = User::where('email', 'jamal@example.com')->firstOrFail();
    expect(Hash::check('motdepasse', $user->password))->toBeTrue();
});

test('user:create refuse un mot de passe trop court', function () {
    $this->artisan('user:create', ['--name' => 'Jamal', '--email' => 'jamal@example.com'])
        ->expectsQuestion('Mot de passe (8 caractères minimum)', 'court')
        ->assertFailed();

    expect(User::count())->toBe(0);
});

test('user:password réinitialise le mot de passe', function () {
    $user = User::factory()->create(['email' => 'sara@example.com']);

    $this->artisan('user:password', ['email' => 'sara@example.com'])
        ->expectsQuestion('Nouveau mot de passe (8 caractères minimum)', 'nouveau123')
        ->assertSuccessful();

    expect(Hash::check('nouveau123', $user->refresh()->password))->toBeTrue();
});

test('user:password signale un email inconnu', function () {
    $this->artisan('user:password', ['email' => 'inconnu@example.com'])
        ->expectsOutputToContain('Aucun utilisateur')
        ->assertFailed();
});
