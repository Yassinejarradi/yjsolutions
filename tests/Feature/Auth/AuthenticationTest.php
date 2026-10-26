<?php

use App\Models\User;

test('la page de connexion s’affiche', function () {
    $this->get('/connexion')->assertOk()->assertSee('Se connecter');
});

test('un visiteur est redirigé vers la connexion', function () {
    $this->get('/tableau-de-bord')->assertRedirect('/connexion');
    $this->get('/parametres/societe')->assertRedirect('/connexion');
});

test('un utilisateur peut se connecter', function () {
    $user = User::factory()->create();

    $this->post('/connexion', ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
});

test('un mauvais mot de passe est refusé avec un message en français', function () {
    $user = User::factory()->create();

    $this->post('/connexion', ['email' => $user->email, 'password' => 'mauvais'])
        ->assertSessionHasErrors(['email' => 'Email ou mot de passe incorrect.']);

    $this->assertGuest();
});

test('un utilisateur peut se déconnecter', function () {
    $this->actingAs(User::factory()->create())
        ->post('/deconnexion')
        ->assertRedirect('/');

    $this->assertGuest();
});

test('il n’y a ni inscription publique ni réinitialisation par email', function (string $url) {
    $this->get($url)->assertNotFound();
})->with(['/register', '/inscription', '/forgot-password', '/reset-password/abc', '/verify-email']);
