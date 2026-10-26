<?php

use App\Models\Setting;
use App\Models\User;
use Database\Seeders\CompanySettingsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(CompanySettingsSeeder::class);
    $this->actingAs(User::factory()->create());
});

function companyData(array $overrides = []): array
{
    return array_merge([
        'company_name' => 'JY SOLUTIONS',
        'address' => 'N° 276 Bd. Ibn Tachefine, 3ème Étage – Casablanca',
        'phone' => '0661 38 63 48',
        'email' => 'JYsolution66@gmail.com',
        'ice' => '004038025000064',
        'rc' => '748069',
        'if_number' => '73385534',
        'tp' => '31905602',
        'cnss' => '7217245',
        'bank_name' => 'Attijariwafa Bank',
        'bank_branch' => 'Casablanca Maârif',
        'rib' => '007780000123456789012345',
    ], $overrides);
}

test('le seeder remplit les données de JY Solutions', function () {
    expect(Setting::current())
        ->company_name->toBe('JY SOLUTIONS')
        ->ice->toBe('004038025000064')
        ->rc->toBe('748069')
        ->if_number->toBe('73385534')
        ->tp->toBe('31905602')
        ->cnss->toBe('7217245')
        ->invoice_prefix->toBe('FAC')
        ->quote_prefix->toBe('DEV')
        ->default_vat_rate->toBe(20)
        ->payment_terms_days->toBe(30)
        ->quote_validity_days->toBe(30);
});

test('relancer le seeder n’écrase pas les modifications', function () {
    Setting::current()->update(['phone' => '0522 00 00 00']);

    $this->seed(CompanySettingsSeeder::class);

    expect(Setting::current()->phone)->toBe('0522 00 00 00')
        ->and(Setting::count())->toBe(1);
});

test('la page société affiche les informations actuelles', function () {
    $this->get('/parametres/societe')
        ->assertOk()
        ->assertSee('004038025000064')
        ->assertSee('Ibn Tachefine');
});

test('les informations de la société peuvent être modifiées', function () {
    $this->put('/parametres/societe', companyData(['phone' => '0522 11 22 33', 'ice' => '004 038 025 000 064']))
        ->assertSessionHasNoErrors()
        ->assertRedirect('/parametres/societe')
        ->assertSessionHas('success');

    $setting = Setting::current();

    expect($setting->phone)->toBe('0522 11 22 33')
        ->and($setting->ice)->toBe('004038025000064')
        ->and($setting->bankLine())->toBe('Attijariwafa Bank – Casablanca Maârif – RIB : 007780000123456789012345');
});

test('un champ facultatif vidé est enregistré vide', function () {
    $this->put('/parametres/societe', companyData(['cnss' => null]))->assertSessionHasNoErrors();

    expect(Setting::current()->cnss)->toBe('');
});

test('l’ICE doit contenir 15 chiffres', function () {
    $this->put('/parametres/societe', companyData(['ice' => '12345']))
        ->assertSessionHasErrors(['ice' => 'Le champ ICE doit contenir exactement 15 chiffres.']);
});

test('la raison sociale est obligatoire', function () {
    $this->put('/parametres/societe', companyData(['company_name' => '']))
        ->assertSessionHasErrors(['company_name' => 'Le champ raison sociale est obligatoire.']);
});

test('un nouveau logo peut être téléversé puis retiré', function () {
    Storage::fake(Setting::LOGO_DISK);

    $this->put('/parametres/societe', companyData(['logo' => UploadedFile::fake()->image('logo.png', 200, 200)]))
        ->assertSessionHasNoErrors();

    $setting = Setting::current();
    expect($setting->logo_path)->toBe('settings/logo.png');
    Storage::disk(Setting::LOGO_DISK)->assertExists('settings/logo.png');
    expect($setting->logoFilePath())->toBe(Storage::disk(Setting::LOGO_DISK)->path('settings/logo.png'));

    $this->delete('/parametres/societe/logo')->assertRedirect('/parametres/societe');

    Storage::disk(Setting::LOGO_DISK)->assertMissing('settings/logo.png');
    expect(Setting::current()->logo_path)->toBeNull()
        ->and(Setting::current()->logoFilePath())->toBe(public_path('logo.png'));
});

test('le logo doit être une image', function () {
    $this->put('/parametres/societe', companyData(['logo' => UploadedFile::fake()->create('logo.pdf', 10, 'application/pdf')]))
        ->assertSessionHasErrors('logo');
});

test('le logo par défaut est servi même sans être connecté', function () {
    auth()->logout();

    $this->get('/logo')->assertOk()->assertHeader('content-type', 'image/png');
});
