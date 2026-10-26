<?php

namespace App\Support;

use App\Models\DocumentLine;
use App\Models\Quote;
use App\Models\Setting;

/**
 * Contenu imprimé d'un document, déjà formaté (montants « 12 345,67 », dates JJ/MM/AAAA).
 *
 * Une seule source pour l'écran en lecture seule, le PDF et l'export Word :
 * les trois affichent exactement les mêmes textes et les mêmes montants.
 */
final readonly class DocumentSheet
{
    /**
     * @param  list<array{0: string, 1: string}>  $dates  [libellé, valeur]
     * @param  list<array{number: int, description: string, quantity: string, unit_price: string, amount: string}>  $lines
     * @param  list<array{0: string, 1: string, 2?: string}>  $totalRows  [libellé, montant, style : '' | 'bold' | 'due']
     * @param  list<array{0: string, 1: string}>  $terms  [libellé, texte]
     * @param  array<string, string>  $company
     */
    public function __construct(
        public string $title,
        public string $numberLabel,
        public ?string $number,
        public string $amountLabel,
        public string $amount,
        public string $clientLabel,
        public array $client,
        public array $dates,
        public array $lines,
        public array $totalRows,
        public string $wordsLabel,
        public string $words,
        public array $terms,
        public ?string $notes,
        public ?string $clientSignature,
        public array $company,
        public ?string $watermark,
        public string $logoPath,
        public string $fileName,
        public DocumentTotals $totals,
    ) {}

    public static function forQuote(Quote $quote): self
    {
        $quote->loadMissing('lines', 'client');
        $lines = $quote->printableLines();
        $totals = $quote->calculateTotals($lines);
        $validDays = (int) $quote->issue_date->diffInDays($quote->valid_until);

        return new self(
            title: 'DEVIS',
            numberLabel: 'N° de devis :',
            number: $quote->number,
            amountLabel: 'Total TTC',
            amount: Money::format($totals->totalTtc, true),
            clientLabel: 'Devis établi pour',
            client: $quote->clientDetails(),
            dates: [
                ['Date du devis :', $quote->issue_date->format('d/m/Y')],
                ['Valable jusqu’au :', $quote->valid_until->format('d/m/Y')],
                ['Réf. demande :', $quote->reference],
            ],
            lines: self::formatLines($lines, $totals),
            totalRows: self::totalRows($totals, 'Net à payer TTC', withTtcRow: false),
            wordsLabel: 'Arrêté le présent devis à la somme de :',
            words: AmountInWords::fromCents($totals->totalTtc),
            terms: array_values(array_filter([
                ['Validité de l’offre :', $validDays.' jours, jusqu’au '.$quote->valid_until->format('d/m/Y')],
                ['Modalités de paiement :', $quote->payment_terms],
                ['Délai de réalisation :', $quote->completion_delay],
            ], fn (array $term) => trim($term[1]) !== '')),
            notes: filled($quote->notes) ? $quote->notes : null,
            clientSignature: 'Bon pour accord – Client',
            company: $quote->companyDetails(),
            watermark: $quote->isDraft() ? 'BROUILLON' : null,
            logoPath: Setting::current()->logoFilePath(),
            fileName: $quote->fileName(),
            totals: $totals,
        );
    }

    /**
     * Logo en data URI (pour dompdf, sans accès au système de fichiers depuis le HTML).
     */
    public function logoDataUri(): string
    {
        $mime = mime_content_type($this->logoPath) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($this->logoPath));
    }

    /**
     * Lignes d'identifiants légaux du pied de page.
     *
     * @return list<string>
     */
    public function footerLines(): array
    {
        return self::companyFooterLines($this->company);
    }

    public function footerTitle(): string
    {
        return self::companyFooterTitle($this->company);
    }

    /**
     * Pied de page à partir des informations de la société (aussi utilisé par l'éditeur).
     *
     * @return list<string>
     */
    public static function companyFooterLines(array $c): array
    {
        $legal = collect([
            'ICE' => $c['ice'] ?? '', 'RC' => $c['rc'] ?? '', 'IF' => $c['if_number'] ?? '',
            'TP' => $c['tp'] ?? '', 'CNSS' => $c['cnss'] ?? '',
        ])->filter()->map(fn ($value, $label) => "$label : $value")->implode(' | ');

        $contact = collect([
            filled($c['phone'] ?? '') ? 'Tél : '.$c['phone'] : null,
            filled($c['email'] ?? '') ? 'Email : '.$c['email'] : null,
        ])->filter()->implode(' | ');

        return array_values(array_filter([$legal, $contact]));
    }

    public static function companyFooterTitle(array $c): string
    {
        return collect([$c['name'] ?? '', $c['address'] ?? ''])->filter()->implode(' – ');
    }

    private static function formatLines($lines, DocumentTotals $totals): array
    {
        return $lines->values()->map(fn (DocumentLine $line, int $i) => [
            'number' => $i + 1,
            'description' => $line->description,
            'quantity' => Quantity::format($line->quantity),
            'unit_price' => $line->unit_price === null ? '' : Money::format($line->unit_price),
            'amount' => Money::format($totals->lineAmounts[$i]),
        ])->all();
    }

    /**
     * Lignes du bloc des totaux. Avec une remise : Sous-total, Remise, puis Total HT.
     */
    public static function totalRows(DocumentTotals $totals, string $dueLabel, bool $withTtcRow): array
    {
        $rows = [];

        if ($totals->hasDiscount()) {
            $rows[] = ['Sous-total', Money::format($totals->subtotal)];
            $rows[] = ['Remise ('.self::percent($totals->discountPercent).' %)', '-'.Money::format($totals->discount)];
        }

        $rows[] = ['Total HT', Money::format($totals->totalHt)];
        $rows[] = ['TVA ('.$totals->vatRate.' %)', Money::format($totals->vat)];

        if ($withTtcRow) {
            $rows[] = ['Total TTC', Money::format($totals->totalTtc, true), 'bold'];
        }

        $rows[] = [$dueLabel, Money::format($totals->totalTtc, true), 'due'];

        return $rows;
    }

    /** "10.00" => "10", "12.50" => "12,5" */
    public static function percent(string $percent): string
    {
        return Quantity::format($percent);
    }
}
