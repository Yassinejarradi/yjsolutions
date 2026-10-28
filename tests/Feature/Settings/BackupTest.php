<?php

use App\Models\Client;
use App\Models\User;
use App\Support\Backup;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    // SQLite refuse VACUUM INTO dans une transaction : on referme celle de RefreshDatabase.
    // Sans risque ici, la base :memory: est recréée à chaque test.
    DB::commit();

    $this->backupDir = storage_path('framework/testing/backups-'.uniqid());
    config(['backup.path' => $this->backupDir]);
});

afterEach(function () {
    File::deleteDirectory($this->backupDir);
    Carbon::setTestNow();
});

/** Ouvre la base SQLite contenue dans une sauvegarde. */
function backupDatabase(string $zipPath): PDO
{
    $zip = new ZipArchive;
    $zip->open($zipPath);
    $file = dirname($zipPath).'/extrait.sqlite';
    file_put_contents($file, $zip->getFromName('database.sqlite'));
    $zip->close();

    return new PDO('sqlite:'.$file);
}

test('backup:run crée un zip avec la base, les logos et les instructions', function () {
    Client::factory()->create(['name' => 'OCP Group']);
    File::ensureDirectoryExists(storage_path('app/private/settings'));
    $logo = storage_path('app/private/settings/logo-test-sauvegarde.png');
    File::put($logo, 'image');

    $this->artisan('backup:run')->expectsOutputToContain('Sauvegarde créée')->assertSuccessful();

    $backups = Backup::all();
    expect($backups)->toHaveCount(1);

    $zipPath = Backup::path($backups[0]['name']);
    $zip = new ZipArchive;
    $zip->open($zipPath);
    expect($zip->locateName('LISEZMOI.txt'))->not->toBeFalse()
        ->and($zip->getFromName('fichiers/settings/logo-test-sauvegarde.png'))->toBe('image');
    $zip->close();

    $names = backupDatabase($zipPath)->query('select name from clients')->fetchAll(PDO::FETCH_COLUMN);
    expect($names)->toBe(['OCP Group']);

    File::delete($logo);
});

test('seules les sauvegardes les plus récentes sont gardées', function () {
    config(['backup.keep' => 2]);

    foreach (['2026-10-01 13:00', '2026-10-02 13:00', '2026-10-03 13:00'] as $moment) {
        Carbon::setTestNow($moment);
        Backup::create();
    }

    expect(array_column(Backup::all(), 'name'))->toBe([
        'sauvegarde-2026-10-03-130000.zip',
        'sauvegarde-2026-10-02-130000.zip',
    ]);
});

test('la sauvegarde est planifiée chaque jour', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(fn ($event) => str_contains($event->command, 'backup:run'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('0 13 * * *');
});

test('la page Sauvegardes permet d’en créer et d’en télécharger une', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/parametres/sauvegardes')->assertOk()->assertSee('Aucune sauvegarde pour l’instant');

    $this->post('/parametres/sauvegardes')
        ->assertRedirect('/parametres/sauvegardes')
        ->assertSessionHas('success');

    $name = Backup::all()[0]['name'];
    $this->get('/parametres/sauvegardes')->assertSee('La plus récente')->assertSee('Télécharger');
    $this->get('/parametres/sauvegardes/'.$name)->assertOk()->assertDownload($name);
});

test('un fichier inconnu ou hors du dossier ne se télécharge pas', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/parametres/sauvegardes/sauvegarde-2026-01-01-000000.zip')->assertNotFound();
    $this->get('/parametres/sauvegardes/..%2F..%2F.env')->assertNotFound();
});

test('les sauvegardes demandent une connexion', function () {
    $this->get('/parametres/sauvegardes')->assertRedirect('/connexion');
    $this->post('/parametres/sauvegardes')->assertRedirect('/connexion');
});
