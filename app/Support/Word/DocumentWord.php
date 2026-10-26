<?php

namespace App\Support\Word;

use App\Support\DocumentSheet;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\JcTable;
use PhpOffice\PhpWord\Style\Image;

/**
 * Export Word (.docx, compatible Word 2007) avec la même mise en page que le PDF :
 * A4, marges 1,8 cm et 3 cm en bas, en-tête du tableau répété, pied de page et « Page X / Y ».
 */
class DocumentWord
{
    private const BLUE = '0057D0';

    private const RED = 'E30613';

    private const MUTED = '6B7280';

    private const LINE = 'D9DEE7';

    /** Largeur utile : 21 cm − 2 × 1,8 cm. */
    private const CONTENT_CM = 17.4;

    /**
     * Écrit le document dans un fichier temporaire et renvoie son chemin.
     */
    public static function save(DocumentSheet $sheet): string
    {
        $path = tempnam(sys_get_temp_dir(), 'jy-word-');
        IOFactory::createWriter(self::build($sheet), 'Word2007')->save($path);

        return $path;
    }

    public static function build(DocumentSheet $sheet): PhpWord
    {
        // Échappe &, < et > dans les textes (sinon « Article & Description » rend le fichier illisible).
        Settings::setOutputEscapingEnabled(true);

        $word = new PhpWord;
        $word->setDefaultFontName('Calibri');
        $word->setDefaultFontSize(10.5);
        $word->setDefaultParagraphStyle(['spaceAfter' => 0, 'spaceBefore' => 0]);
        $word->getDocInfo()->setTitle($sheet->fileName)->setCreator($sheet->company['name'] ?? '');

        $section = $word->addSection([
            // A4 en twips entiers (paperSize 'A4' donnerait 11905.51, que Word arrondit mal).
            'pageSizeW' => 11906,
            'pageSizeH' => 16838,
            'marginTop' => self::cm(1.8),
            'marginLeft' => self::cm(1.8),
            'marginRight' => self::cm(1.8),
            'marginBottom' => self::cm(3),
            'footerHeight' => self::cm(0.8),
        ]);

        if ($sheet->watermark) {
            self::addWatermark($section, $sheet->watermark);
        }

        self::addFooter($section, $sheet);
        self::addHeading($section, $sheet);
        self::addParties($section, $sheet);
        self::addLines($section, $sheet);
        self::addTotals($section, $sheet);

        if ($sheet->words !== null) {
            $words = $section->addTextRun(['spaceBefore' => self::pt(14), 'spaceAfter' => self::pt(8)]);
            $words->addText($sheet->wordsLabel.' ', ['bold' => true]);
            $words->addText($sheet->words, ['italic' => true, 'color' => self::MUTED]);
        }

        foreach ($sheet->terms as [$label, $text]) {
            $run = $section->addTextRun(['spaceAfter' => self::pt(2)]);
            $run->addText($label.' ', ['bold' => true]);
            $run->addText($text);
        }

        if ($sheet->notes) {
            self::addMultiline($section->addTextRun(['spaceBefore' => self::pt(10)]), $sheet->notes, ['size' => 9, 'color' => self::MUTED]);
        }

        if ($sheet->clientSignature || $sheet->companySignature) {
            self::addSignatures($section, $sheet);
        }

        // Un document Word doit finir par un paragraphe : minuscule, pour ne pas créer de page vide.
        $section->addText('', ['size' => 1], ['spaceAfter' => 0]);

        return $word;
    }

    private static function addHeading(Section $section, DocumentSheet $sheet): void
    {
        $table = $section->addTable(['width' => 100 * 50, 'unit' => 'pct']);
        $row = $table->addRow();

        $logo = $row->addCell(self::cm(7));
        if ($sheet->logoPath) {
            // Largeur en points (1 cm = 28,35 pt), comme dans le PDF.
            $logo->addImage($sheet->logoPath, ['width' => (int) round($sheet->logoWidthCm * 28.35)]);
        }

        $right = $row->addCell(self::cm(self::CONTENT_CM - 7));
        $alignRight = ['alignment' => Jc::RIGHT];
        $right->addText($sheet->title, ['size' => 30, 'bold' => true, 'color' => self::BLUE], $alignRight);

        if ($sheet->number) {
            $run = $right->addTextRun($alignRight);
            $run->addText($sheet->numberLabel.' ', ['bold' => true]);
            $run->addText($sheet->number, ['bold' => true, 'color' => self::RED]);
        }

        if ($sheet->amountLabel !== null) {
            $right->addText($sheet->amountLabel, ['size' => 9, 'color' => self::MUTED], $alignRight + ['spaceBefore' => self::pt(10)]);
            $right->addText($sheet->amount, ['size' => 17, 'bold' => true], $alignRight);
        }

        // Trait rouge sous l'en-tête.
        $section->addText('', ['size' => 2], [
            'borderBottomSize' => 16, 'borderBottomColor' => self::RED,
            'spaceBefore' => self::pt(6), 'spaceAfter' => self::pt(14),
        ]);
    }

    private static function addParties(Section $section, DocumentSheet $sheet): void
    {
        $table = $section->addTable(['width' => 100 * 50, 'unit' => 'pct']);
        $row = $table->addRow();

        $client = $row->addCell(self::cm(10));
        $client->addText($sheet->clientLabel, ['color' => self::MUTED]);
        $client->addText($sheet->client['name'], ['size' => 12.5, 'bold' => true], ['spaceBefore' => self::pt(2)]);
        if ($sheet->client['address']) {
            $client->addText($sheet->client['address']);
        }
        if ($sheet->client['ice']) {
            $client->addText('ICE : '.$sheet->client['ice'], [], ['spaceBefore' => self::pt(5)]);
        }

        $meta = $row->addCell(self::cm(self::CONTENT_CM - 10))->addTable(['alignment' => JcTable::END]);
        foreach ($sheet->dates as [$label, $value]) {
            $line = $meta->addRow();
            $line->addCell(self::cm(3.6))->addText($label, [], ['alignment' => Jc::RIGHT, 'spaceAfter' => self::pt(3)]);
            $line->addCell(self::cm(3.4))->addText($value, ['color' => self::MUTED], ['alignment' => Jc::RIGHT]);
        }
    }

    private static function addLines(Section $section, DocumentSheet $sheet): void
    {
        $section->addText('', ['size' => 6]);

        $widths = [0.9, self::CONTENT_CM - 0.9 - 2 - 2.8 - 3, 2, 2.8, 3];
        $headers = array_values($sheet->columns);
        $align = [Jc::CENTER, Jc::LEFT, Jc::RIGHT, Jc::RIGHT, Jc::RIGHT];

        $table = $section->addTable(['width' => 100 * 50, 'unit' => 'pct', 'cellMarginLeft' => 100, 'cellMarginRight' => 100]);

        // Ligne d'en-tête répétée en haut de chaque page.
        $head = $table->addRow(null, ['tblHeader' => true, 'cantSplit' => true]);
        foreach ($headers as $i => $label) {
            $head->addCell(self::cm($widths[$i]), ['bgColor' => self::BLUE, 'valign' => 'center'])
                ->addText($label, ['bold' => true, 'color' => 'FFFFFF'], ['alignment' => $align[$i], 'spaceBefore' => 80, 'spaceAfter' => 80]);
        }

        $cell = ['borderBottomSize' => 6, 'borderBottomColor' => self::LINE];
        $paragraph = ['spaceBefore' => 80, 'spaceAfter' => 80];

        foreach ($sheet->lines as $line) {
            $row = $table->addRow(null, ['cantSplit' => true]);
            $row->addCell(self::cm($widths[0]), $cell)->addText((string) $line['number'], ['color' => self::MUTED], $paragraph + ['alignment' => Jc::CENTER]);
            self::addMultiline($row->addCell(self::cm($widths[1]), $cell)->addTextRun($paragraph), $line['description']);
            $row->addCell(self::cm($widths[2]), $cell)->addText($line['quantity'], [], $paragraph + ['alignment' => Jc::RIGHT]);
            $row->addCell(self::cm($widths[3]), $cell)->addText($line['unit_price'], [], $paragraph + ['alignment' => Jc::RIGHT]);
            $row->addCell(self::cm($widths[4]), $cell)->addText($line['amount'], ['bold' => true], $paragraph + ['alignment' => Jc::RIGHT]);
        }
    }

    private static function addTotals(Section $section, DocumentSheet $sheet): void
    {
        $section->addText('', ['size' => 6]);

        $table = $section->addTable(['alignment' => JcTable::END, 'cellMarginLeft' => 120, 'cellMarginRight' => 120]);

        foreach ($sheet->totalRows as $row) {
            $style = $row[2] ?? '';
            $cellStyle = $style === 'due' ? ['bgColor' => self::BLUE] : [];
            $font = match ($style) {
                'due' => ['bold' => true, 'color' => 'FFFFFF'],
                'bold' => ['bold' => true],
                default => [],
            };

            $tr = $table->addRow(null, ['cantSplit' => true]);
            $tr->addCell(self::cm(4.6), $cellStyle)->addText($row[0], $font, ['spaceBefore' => 60, 'spaceAfter' => 60]);
            $tr->addCell(self::cm(3.6), $cellStyle)->addText($row[1], $font, ['alignment' => Jc::RIGHT, 'spaceBefore' => 60, 'spaceAfter' => 60]);
        }
    }

    private static function addSignatures(Section $section, DocumentSheet $sheet): void
    {
        $section->addText('', [], ['spaceBefore' => self::pt(20)]);

        $table = $section->addTable(['width' => 100 * 50, 'unit' => 'pct']);
        $row = $table->addRow(self::cm(1.6), ['cantSplit' => true]);

        foreach ([$sheet->clientSignature, $sheet->companySignature] as $signature) {
            $cell = $row->addCell(self::cm(self::CONTENT_CM / 2));
            if ($signature) {
                $cell->addText($signature[0], ['bold' => true, 'color' => self::BLUE], ['alignment' => Jc::CENTER]);
                $cell->addText($signature[1], ['size' => 8.5, 'color' => self::MUTED], ['alignment' => Jc::CENTER]);
            }
        }
    }

    private static function addFooter(Section $section, DocumentSheet $sheet): void
    {
        if (! $sheet->showFooter && ! $sheet->showPageNumbers) {
            return;
        }

        $footer = $section->addFooter();
        $center = ['alignment' => Jc::CENTER];

        if ($sheet->showFooter) {
            $footer->addText($sheet->footerTitle(), ['size' => 8, 'bold' => true, 'color' => self::BLUE], $center + [
                'borderTopSize' => 12, 'borderTopColor' => self::RED, 'spaceBefore' => 0,
            ]);
            foreach ($sheet->footerLines() as $line) {
                $footer->addText($line, ['size' => 8, 'color' => self::MUTED], $center);
            }
        }

        if ($sheet->showPageNumbers) {
            $footer->addPreserveText('Page {PAGE} / {NUMPAGES}', ['size' => 8, 'color' => self::MUTED], $center);
        }
    }

    /**
     * Filigrane : Word n'accepte qu'une image, dessinée ici avec GD et la police Carlito.
     */
    private static function addWatermark(Section $section, string $text): void
    {
        $image = WatermarkImage::path($text);

        $section->addHeader()->addWatermark($image, [
            'width' => 430,
            'height' => 430,
            'positioning' => Image::POSITION_ABSOLUTE,
            'posHorizontal' => Image::POSITION_HORIZONTAL_CENTER,
            'posHorizontalRel' => Image::POSITION_RELATIVE_TO_PAGE,
            'posVertical' => Image::POSITION_VERTICAL_CENTER,
            'posVerticalRel' => Image::POSITION_RELATIVE_TO_PAGE,
        ]);
    }

    /** Centimètres => twips entiers (Word refuse les largeurs décimales). */
    private static function cm(float $cm): int
    {
        return (int) round(Converter::cmToTwip($cm));
    }

    private static function pt(float $points): int
    {
        return (int) round(Converter::pointToTwip($points));
    }

    private static function addMultiline($run, string $text, array $font = []): void
    {
        foreach (preg_split('/\R/', $text) as $i => $line) {
            if ($i > 0) {
                $run->addTextBreak();
            }
            $run->addText($line, $font);
        }
    }
}
