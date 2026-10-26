<?php

use App\Livewire\QuoteEditor;
use App\Models\Quote;
use App\Models\Setting;
use App\Models\User;
use App\Support\DocumentLayout;
use App\Support\DocumentSheet;
use App\Support\Word\DocumentWord;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Carbon::setTestNow('2026-10-26 10:00:00');
    Storage::fake('local');
    $this->actingAs(User::factory()->create());

    Setting::current()->update([
        'company_name' => 'JY SOLUTIONS', 'address' => 'Casablanca', 'phone' => '0661 38 63 48',
        'email' => 'jy@example.ma', 'ice' => '004038025000064',
    ]);
    $this->quote = Quote::factory()->withLines([['Conseil', '1', 100000]])->create();
});

function pdfHtml(Quote $quote): string
{
    return view('pdf.document', ['sheet' => DocumentSheet::forQuote($quote->fresh())])->render();
}

test('un devis sans changement a la mise en page d’origine', function () {
    $layout = $this->quote->resolvedLayout();

    expect($layout['labels']['title'])->toBe('DEVIS')
        ->and($layout['logo']['mode'])->toBe('default')
        ->and(collect($layout['show'])->every(fn ($shown) => $shown))->toBeTrue();
});

test('les textes modifiés sur la feuille sont imprimés, et seules les différences sont enregistrées', function () {
    Livewire::test(QuoteEditor::class, ['quote' => $this->quote])
        ->set('layout.labels.title', 'PROPOSITION')
        ->set('layout.labels.col_description', 'Prestation')
        ->set('layout.labels.due', 'Montant total')
        ->call('save')
        ->assertReturned(true);

    expect($this->quote->fresh()->layout)->toBe([
        'labels' => ['title' => 'PROPOSITION', 'col_description' => 'Prestation', 'due' => 'Montant total'],
    ]);

    $html = pdfHtml($this->quote);
    expect($html)->toContain('<h1>PROPOSITION</h1>')
        ->toContain('Prestation</th>')
        ->toContain('Montant total')
        ->not->toContain('Net à payer TTC');
});

test('les éléments masqués disparaissent de l’écran, du PDF et du Word', function () {
    Livewire::test(QuoteEditor::class, ['quote' => $this->quote])
        ->set('layout.show.words', false)
        ->set('layout.show.client_signature', false)
        ->set('layout.show.footer', false)
        ->set('layout.show.page_numbers', false)
        ->set('layout.show.amount', false)
        ->assertDontSee('Arrêté le présent devis')
        ->call('save');

    $sheet = DocumentSheet::forQuote($this->quote->fresh());
    expect($sheet->words)->toBeNull()
        ->and($sheet->clientSignature)->toBeNull()
        ->and($sheet->companySignature)->not->toBeNull()
        ->and($sheet->showFooter)->toBeFalse()
        ->and($sheet->showPageNumbers)->toBeFalse()
        ->and($sheet->amountLabel)->toBeNull();

    $html = pdfHtml($this->quote);
    expect($html)->not->toContain('Arrêté le présent devis')
        ->not->toContain('Bon pour accord')
        ->not->toContain('class="footer"');

    $path = DocumentWord::save($sheet);
    $zip = new ZipArchive;
    $zip->open($path);
    expect($zip->getFromName('word/document.xml'))->not->toContain('Arrêté le présent devis')
        ->and($zip->locateName('word/footer1.xml'))->toBeFalse();
    $zip->close();
    unlink($path);
});

test('le logo peut être retiré puis remis', function () {
    $editor = Livewire::test(QuoteEditor::class, ['quote' => $this->quote])
        ->call('setLogoMode', 'hidden')
        ->assertSee('+ Ajouter un logo')
        ->call('save');

    expect(DocumentSheet::forQuote($this->quote->fresh())->logoPath)->toBeNull()
        ->and(pdfHtml($this->quote))->not->toContain('<img');

    $editor->call('setLogoMode', 'default')->call('save');
    expect($this->quote->fresh()->layout)->toBeNull()
        ->and(pdfHtml($this->quote))->toContain('<img src="data:image/png;base64,');
});

test('un autre logo peut être téléversé pour ce document, en trois tailles', function () {
    Livewire::test(QuoteEditor::class, ['quote' => $this->quote])
        ->set('logoUpload', UploadedFile::fake()->image('partenaire.png', 300, 200))
        ->assertHasNoErrors()
        ->call('setLogoSize', 'large')
        ->call('save');

    $layout = $this->quote->fresh()->resolvedLayout();
    expect($layout['logo']['mode'])->toBe('custom')
        ->and($layout['logo']['size'])->toBe('large');
    Storage::disk('local')->assertExists($layout['logo']['path']);

    $this->get(route('document-logo', basename($layout['logo']['path'])))->assertOk();
    expect(pdfHtml($this->quote))->toContain('style="width: 3.2cm"');
});

test('seule une image PNG ou JPG est acceptée comme logo', function () {
    Livewire::test(QuoteEditor::class, ['quote' => $this->quote])
        ->set('logoUpload', UploadedFile::fake()->create('logo.pdf', 100, 'application/pdf'))
        ->assertHasErrors(['logoUpload'])
        ->assertSet('layout.logo.mode', 'default');
});

test('les logos de documents demandent d’être connecté', function () {
    auth()->logout();

    $this->get('/logos-documents/abc.png')->assertRedirect('/connexion');
});

test('les informations de la société peuvent être changées sur un seul document', function () {
    Livewire::test(QuoteEditor::class, ['quote' => $this->quote])
        ->assertSet('layout.company.phone', '0661 38 63 48')
        ->set('layout.company.phone', '0522 00 00 00')
        ->set('layout.company.email', '')
        ->call('save');

    expect($this->quote->fresh()->layout)->toBe(['company' => ['phone' => '0522 00 00 00', 'email' => '']]);

    $footer = DocumentSheet::forQuote($this->quote->fresh())->footerLines();
    expect(implode(' ', $footer))->toContain('Tél : 0522 00 00 00')->not->toContain('Email');

    // Les Paramètres et les autres devis ne changent pas.
    expect(Setting::current()->phone)->toBe('0661 38 63 48')
        ->and(Quote::factory()->withLines()->create()->companyDetails()['phone'])->toBe('0661 38 63 48');
});

test('une mise en page peut devenir le modèle des prochains devis, puis être rétablie', function () {
    $untouched = Quote::factory()->withLines()->create();

    Livewire::test(QuoteEditor::class, ['quote' => $this->quote])
        ->set('layout.labels.title', 'OFFRE')
        ->set('layout.show.reference', false)
        ->set('layout.company.phone', 'pas dans le modèle')
        ->call('saveLayoutAsTemplate')
        ->assertSee('C’est maintenant la mise en page des prochains documents.');

    expect(Setting::current()->document_layouts['quote'])->toBe([
        'labels' => ['title' => 'OFFRE'],
        'show' => ['reference' => false],
    ]);

    // Nouveaux devis et brouillons non personnalisés suivent le modèle.
    expect(Quote::newDraft()->resolvedLayout()['labels']['title'])->toBe('OFFRE')
        ->and($untouched->fresh()->resolvedLayout()['show']['reference'])->toBeFalse();
    Livewire::test(QuoteEditor::class)->assertSet('layout.labels.title', 'OFFRE');

    $this->get('/parametres/documents')->assertSee('Personnalisée.');
    $this->delete('/parametres/documents/mise-en-page/quote')->assertSessionHas('success');

    expect(Setting::current()->document_layouts)->toBeNull()
        ->and(Quote::newDraft()->resolvedLayout()['labels']['title'])->toBe('DEVIS');
});

test('« Rétablir » ramène le document au modèle', function () {
    Livewire::test(QuoteEditor::class, ['quote' => $this->quote])
        ->set('layout.labels.title', 'AUTRE')
        ->call('setLogoMode', 'hidden')
        ->call('resetLayout')
        ->assertSet('layout.labels.title', 'DEVIS')
        ->assertSet('layout.logo.mode', 'default')
        ->call('save');

    expect($this->quote->fresh()->layout)->toBeNull();
});

test('la mise en page et le logo d’un devis envoyé sont figés', function () {
    $this->quote->update(['layout' => ['labels' => ['title' => 'PROPOSITION']]]);
    $this->quote->send();

    // Le modèle et le logo des Paramètres changent ensuite…
    Setting::current()->update(['document_layouts' => ['quote' => ['labels' => ['title' => 'NOUVEAU'], 'show' => ['footer' => false]]]]);
    Setting::current()->update(['logo_path' => 'settings/autre.png']);

    $sent = $this->quote->fresh();
    $layout = $sent->resolvedLayout();
    expect($layout['labels']['title'])->toBe('PROPOSITION')
        ->and($layout['show']['footer'])->toBeTrue()
        ->and($layout['logo']['mode'])->toBe('custom')
        ->and($layout['logo']['frozen'])->toBeTrue();
    Storage::disk('local')->assertExists($layout['logo']['path']);

    // …et une copie de ce devis reprend le logo actuel, avec les textes du devis.
    $copy = $sent->duplicate()->fresh();
    expect($copy->resolvedLayout()['logo']['mode'])->toBe('default')
        ->and($copy->resolvedLayout()['labels']['title'])->toBe('PROPOSITION');
});

test('les saisies inconnues ou dangereuses sont ignorées', function () {
    $clean = DocumentLayout::sanitize('quote', [
        'logo' => ['mode' => 'custom', 'path' => '../../.env', 'size' => 'énorme'],
        'labels' => ['title' => str_repeat('A', 500), 'inconnu' => 'x'],
        'show' => ['footer' => '0', 'pirate' => true],
        'company' => ['phone' => ['tableau']],
    ]);

    expect($clean['logo'])->toBe(['mode' => 'default', 'path' => null, 'size' => 'medium', 'frozen' => false])
        ->and(mb_strlen($clean['labels']['title']))->toBe(150)
        ->and($clean['labels'])->not->toHaveKey('inconnu')
        ->and($clean['show']['footer'])->toBeFalse()
        ->and($clean['show'])->not->toHaveKey('pirate')
        ->and($clean['company']['phone'])->toBeNull();
});

test('l’écran de lecture d’un devis envoyé affiche ses textes personnalisés', function () {
    $this->quote->update(['layout' => ['labels' => ['title' => 'PROPOSITION', 'col_amount' => 'Total ligne']]]);
    $this->quote->send();

    $this->get("/devis/{$this->quote->id}")->assertOk()->assertSee('PROPOSITION')->assertSee('Total ligne');
});
