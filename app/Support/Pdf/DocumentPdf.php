<?php

namespace App\Support\Pdf;

use App\Support\DocumentSheet;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * PDF A4 d'un document (vue pdf.document), avec « Page X / Y » en bas de chaque page.
 */
class DocumentPdf
{
    public static function render(DocumentSheet $sheet): string
    {
        $pdf = Pdf::loadView('pdf.document', ['sheet' => $sheet])->setPaper('a4');

        $dompdf = $pdf->getDomPDF();
        $dompdf->render();

        // Numéro de page sous le pied de page (dompdf remplace {PAGE_NUM} et {PAGE_COUNT}).
        $canvas = $dompdf->getCanvas();
        $metrics = $dompdf->getFontMetrics();
        $font = $metrics->getFont('Carlito') ?? $metrics->getFont('helvetica');
        $size = 8;
        $text = 'Page {PAGE_NUM} / {PAGE_COUNT}';
        $width = $metrics->getTextWidth('Page 1 / 1', $font, $size);

        $canvas->page_text(($canvas->get_width() - $width) / 2, $canvas->get_height() - 16, $text, $font, $size, [0.42, 0.45, 0.5]);

        return $dompdf->output();
    }
}
