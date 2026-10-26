<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('l’utilisateur peut changer son mot de passe', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from('/profil')
        ->put('/mot-de-passe', [
            'current_password' => 'password',
            'password' => 'nouveau-mot-de-passe',
            'password_confirmation' => 'nouveau-mot-de-passe',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profil')
        ->assertSessionHas('success');

    expect(Hash::check('nouveau-mot-de-passe', $user->refresh()->password))->toBeTrue();
});

test('le mot de passe actuel doit être correct', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from('/profil')
        ->put('/mot-de-passe', [
            'current_password' => 'faux',
            'password' => 'nouveau-mot-de-passe',
            'password_confirmation' => 'nouveau-mot-de-passe',
        ])
        ->assertSessionHasErrorsIn('updatePassword', 'current_password')
        ->assertRedirect('/profil');
});
