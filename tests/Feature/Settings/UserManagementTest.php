<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->me = User::factory()->create(['name' => 'Moi']);
    $this->actingAs($this->me);
});

test('la liste des utilisateurs s’affiche', function () {
    User::factory()->create(['name' => 'Sara']);

    $this->get('/parametres/utilisateurs')->assertOk()->assertSee('Moi')->assertSee('Sara');
});

test('un utilisateur peut être créé', function () {
    $this->get('/parametres/utilisateurs/nouveau')->assertOk();

    $this->post('/parametres/utilisateurs', [
        'name' => 'Sara',
        'email' => 'sara@example.com',
        'password' => 'motdepasse',
        'password_confirmation' => 'motdepasse',
    ])->assertRedirect('/parametres/utilisateurs')->assertSessionHas('success');

    $sara = User::where('email', 'sara@example.com')->firstOrFail();
    expect(Hash::check('motdepasse', $sara->password))->toBeTrue();
});

test('le mot de passe est obligatoire à la création', function () {
    $this->post('/parametres/utilisateurs', ['name' => 'Sara', 'email' => 'sara@example.com'])
        ->assertSessionHasErrors(['password' => 'Le champ mot de passe est obligatoire.']);
});

test('l’email doit être unique', function () {
    $this->post('/parametres/utilisateurs', [
        'name' => 'Double',
        'email' => $this->me->email,
        'password' => 'motdepasse',
        'password_confirmation' => 'motdepasse',
    ])->assertSessionHasErrors('email');
});

test('modifier un utilisateur sans mot de passe garde l’ancien', function () {
    $sara = User::factory()->create();

    $this->get("/parametres/utilisateurs/{$sara->id}/modifier")->assertOk();

    $this->put("/parametres/utilisateurs/{$sara->id}", ['name' => 'Sara B.', 'email' => $sara->email, 'password' => ''])
        ->assertSessionHasNoErrors()
        ->assertRedirect('/parametres/utilisateurs');

    $sara->refresh();
    expect($sara->name)->toBe('Sara B.')
        ->and(Hash::check('password', $sara->password))->toBeTrue();
});

test('le mot de passe d’un utilisateur peut être changé', function () {
    $sara = User::factory()->create();

    $this->put("/parametres/utilisateurs/{$sara->id}", [
        'name' => $sara->name,
        'email' => $sara->email,
        'password' => 'nouveau123',
        'password_confirmation' => 'nouveau123',
    ])->assertSessionHasNoErrors();

    expect(Hash::check('nouveau123', $sara->refresh()->password))->toBeTrue();
});

test('un autre utilisateur peut être supprimé', function () {
    $sara = User::factory()->create();

    $this->delete("/parametres/utilisateurs/{$sara->id}")->assertRedirect('/parametres/utilisateurs');

    expect(User::find($sara->id))->toBeNull();
});

test('on ne peut pas supprimer son propre compte', function () {
    $this->from('/parametres/utilisateurs')
        ->delete("/parametres/utilisateurs/{$this->me->id}")
        ->assertRedirect('/parametres/utilisateurs')
        ->assertSessionHas('error');

    expect(User::find($this->me->id))->not->toBeNull();
});
