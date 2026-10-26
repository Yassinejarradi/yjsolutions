<?php

use App\Livewire\QuoteEditor;
use App\Models\Quote;
use App\Models\User;
use App\Support\DocumentSheet;
use App\Support\Pdf\DocumentPdf;
use App\Support\Word\DocumentWord;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

beforeEach(function () {
    Carbon::setTestNow('2026-10-26 10:00:00');
    $this->actingAs(User::factory()->create());

    // Arrondis délicats : 0,5 centime par ligne, remise à deux décimales, TVA à 14 %.
    $this->quote = Quote::factory()
        ->withLines([
            ["Audit organisationnel\nEntretiens et rapport", '3', 33333],   // 999,99
            ['Accompagnement', '0.333', 150001],                          // 499,50333 → 499,50
            ['Frais de déplacement', '1.5', 12345],                       // 185,175 → 185,18
        ])
        ->create(['vat_rate' => 14, 'discount_percent' => '7.25', 'reference' => 'Demande & suivi']);
});

/** Textes qui doivent apparaître à l'identique partout. */
function expectedTexts(DocumentSheet $sheet): array
{
    return array_merge(
        array_column($sheet->lines, 'amount'),
        array_column($sheet->totalRows, 1),
        [$sheet->words, $sheet->amount],
    );
}

test('les totaux enregistrés sont ceux du calculateur', function () {
    $sheet = DocumentSheet::forQuote($this->quote);

    expect(array_column($sheet->lines, 'amount'))->toBe(['999,99', '499,50', '185,18'])
        ->and($sheet->totals->subtotal)->toBe(168467)
        ->and($sheet->totals->discount)->toBe(12214)       // 7,25 % de 1 684,67 = 122,138… → 122,14
        ->and($sheet->totals->totalHt)->toBe(156253)
        ->and($sheet->totals->vat)->toBe(21875)            // 14 % de 1 562,53 = 218,754… → 218,75
        ->and($sheet->totals->totalTtc)->toBe(178128)
        ->and($this->quote->fresh()->total_ttc)->toBe(178128)
        ->and($sheet->words)->toBe('Mille sept cent quatre-vingt-un dirhams et vingt-huit centimes TTC');
});

test('l’éditeur affiche exactement les montants du PDF', function () {
    $sheet = DocumentSheet::forQuote($this->quote);
    $editor = Livewire::test(QuoteEditor::class, ['quote' => $this->quote]);

    // Mêmes totaux, calculés à partir de la saisie à l'écran.
    expect($editor->instance()->currentTotals())->toEqual($sheet->totals);

    foreach (expectedTexts($sheet) as $text) {
        $editor->assertSee($text);
    }
});

test('le PDF et l’écran de lecture contiennent les mêmes textes', function () {
    $this->quote->send();
    $sheet = DocumentSheet::forQuote($this->quote->fresh());

    $pdfHtml = view('pdf.document', ['sheet' => $sheet])->render();
    $screen = $this->get("/devis/{$this->quote->id}")->assertOk();

    foreach (expectedTexts($sheet) as $text) {
        expect($pdfHtml)->toContain(e($text));
        $screen->assertSee($text);
    }

    expect($pdfHtml)->toContain('DEV2026-001')->toContain('Demande &amp; suivi');
});

test('le PDF d’un brouillon porte le filigrane BROUILLON et aucun numéro', function () {
    $html = view('pdf.document', ['sheet' => DocumentSheet::forQuote($this->quote)])->render();

    expect($html)->toContain('class="watermark">BROUILLON')
        ->not->toContain('N° de devis :');

    $this->quote->send();
    $html = view('pdf.document', ['sheet' => DocumentSheet::forQuote($this->quote->fresh())])->render();

    expect($html)->not->toContain('class="watermark"')->toContain('N° de devis :');
});

test('le PDF est un vrai fichier PDF A4', function () {
    $pdf = DocumentPdf::render(DocumentSheet::forQuote($this->quote));

    expect($pdf)->toStartWith('%PDF-')
        ->and($pdf)->toContain('/MediaBox [0.000 0.000 595.280 841.890]'); // A4 en points
});

test('les adresses PDF et Word renvoient les fichiers', function () {
    $this->get("/devis/{$this->quote->id}/pdf")
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('Content-Disposition', 'inline; filename="Devis_brouillon_'.$this->quote->id.'.pdf"');

    $this->get("/devis/{$this->quote->id}/word")
        ->assertOk()
        ->assertDownload('Devis_brouillon_'.$this->quote->id.'.docx');
});

test('l’export Word contient les mêmes textes que le PDF', function () {
    $this->quote->send();
    $sheet = DocumentSheet::forQuote($this->quote->fresh());

    $path = DocumentWord::save($sheet);
    $zip = new ZipArchive;
    expect($zip->open($path))->toBeTrue();
    $xml = $zip->getFromName('word/document.xml');
    $footer = $zip->getFromName('word/footer1.xml');
    $zip->close();
    unlink($path);

    // Le XML doit être valide (sinon Word refuse d'ouvrir le fichier).
    expect((new DOMDocument)->loadXML($xml))->toBeTrue();

    foreach (expectedTexts($sheet) as $text) {
        expect($xml)->toContain(htmlspecialchars($text, ENT_XML1));
    }

    expect($xml)->toContain('DEV2026-001')
        ->toContain('Article &amp; Description')
        ->toContain('w:tblHeader')                 // en-tête du tableau répété sur chaque page
        ->and($footer)->toContain('NUMPAGES');     // « Page X / Y »
});
