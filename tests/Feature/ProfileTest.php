<?php

use App\Models\User;

test('la page profil s’affiche', function () {
    $this->actingAs(User::factory()->create())
        ->get('/profil')
        ->assertOk()
        ->assertSee('Mes informations');
});

test('l’utilisateur peut modifier son nom et son email', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch('/profil', ['name' => 'Jamal Y.', 'email' => 'jamal@example.com'])
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profil');

    expect($user->refresh())
        ->name->toBe('Jamal Y.')
        ->email->toBe('jamal@example.com');
});

test('le profil ne propose pas de supprimer son compte', function () {
    $this->actingAs(User::factory()->create())
        ->delete('/profil')
        ->assertStatus(405);
});
