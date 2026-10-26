<?php

namespace App\Support;

use App\Models\DocumentLine;
use App\Models\Quote;

/**
 * Contenu imprimé d'un document, déjà formaté (montants « 12 345,67 », dates JJ/MM/AAAA)
 * et mis en page selon sa mise en page (App\Support\DocumentLayout).
 *
 * Une seule source pour l'écran en lecture seule, le PDF et l'export Word :
 * les trois affichent exactement les mêmes textes et les mêmes montants.
 */
final readonly class DocumentSheet
{
    /**
     * @param  list<array{0: string, 1: string}>  $dates  [libellé, valeur]
     * @param  array{number: string, description: string, quantity: string, unit_price: string, amount: string}  $columns
     * @param  list<array{number: int, description: string, quantity: string, unit_price: string, amount: string}>  $lines
     * @param  list<array{0: string, 1: string, 2?: string}>  $totalRows  [libellé, montant, style : '' | 'bold' | 'due']
     * @param  list<array{0: string, 1: string}>  $terms  [libellé, texte]
     * @param  array{0: string, 1: string}|null  $clientSignature  [titre, mention]
     * @param  array{0: string, 1: string}|null  $companySignature  [titre, mention]
     * @param  array<string, string>  $company
     */
    public function __construct(
        public string $title,
        public string $numberLabel,
        public ?string $number,
        public ?string $amountLabel,
        public string $amount,
        public string $clientLabel,
        public array $client,
        public array $dates,
        public array $columns,
        public array $lines,
        public array $totalRows,
        public ?string $wordsLabel,
        public ?string $words,
        public array $terms,
        public ?string $notes,
        public ?array $clientSignature,
        public ?array $companySignature,
        public array $company,
        public bool $showFooter,
        public bool $showPageNumbers,
        public ?string $watermark,
        public ?string $logoPath,
        public float $logoWidthCm,
        public string $fileName,
        public DocumentTotals $totals,
    ) {}

    public static function forQuote(Quote $quote): self
    {
        $quote->loadMissing('lines', 'client');
        $layout = $quote->resolvedLayout();
        $label = $layout['labels'];
        $show = $layout['show'];

        $lines = $quote->printableLines();
        $totals = $quote->calculateTotals($lines);
        $validDays = (int) $quote->issue_date->diffInDays($quote->valid_until);
        $company = $quote->companyDetails();

        $client = $quote->clientDetails();
        if (! $show['client_ice']) {
            $client['ice'] = '';
        }

        return new self(
            title: $label['title'],
            numberLabel: $label['number'],
            number: $quote->number,
            amountLabel: $show['amount'] ? $label['amount'] : null,
            amount: Money::format($totals->totalTtc, true),
            clientLabel: $label['client'],
            client: $client,
            dates: array_values(array_filter([
                [$label['date'], $quote->issue_date->format('d/m/Y')],
                [$label['due_date'], $quote->valid_until->format('d/m/Y')],
                $show['reference'] ? [$label['reference'], $quote->reference] : null,
            ])),
            columns: self::columns($label),
            lines: self::formatLines($lines, $totals),
            totalRows: self::totalRows($totals, $label),
            wordsLabel: $show['words'] ? $label['words'] : null,
            words: $show['words'] ? AmountInWords::fromCents($totals->totalTtc) : null,
            terms: array_values(array_filter([
                $show['validity'] ? [$label['validity'], $validDays.' jours, jusqu’au '.$quote->valid_until->format('d/m/Y')] : null,
                $show['payment_terms'] ? [$label['payment_terms'], $quote->payment_terms] : null,
                $show['completion_delay'] ? [$label['completion_delay'], $quote->completion_delay] : null,
            ], fn (?array $term) => $term && trim($term[1]) !== '')),
            notes: $show['notes'] && filled($quote->notes) ? $quote->notes : null,
            clientSignature: $show['client_signature'] ? [$label['client_signature'], $label['client_signature_note']] : null,
            companySignature: $show['company_signature'] ? [self::companySignatureTitle($label, $company), $label['company_signature_note']] : null,
            company: $company,
            showFooter: $show['footer'],
            showPageNumbers: $show['page_numbers'],
            watermark: $quote->isDraft() ? 'BROUILLON' : null,
            logoPath: DocumentLayout::logoFile($layout),
            logoWidthCm: DocumentLayout::logoWidthCm($layout),
            fileName: $quote->fileName(),
            totals: $totals,
        );
    }

    /**
     * Logo en data URI (pour dompdf, sans accès au système de fichiers depuis le HTML).
     */
    public function logoDataUri(): ?string
    {
        if ($this->logoPath === null) {
            return null;
        }

        $mime = mime_content_type($this->logoPath) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($this->logoPath));
    }

    /**
     * Largeur du logo à l'écran, en pixels (la feuille fait 210 mm ≈ 794 px).
     */
    public function logoWidthPx(): int
    {
        return (int) round($this->logoWidthCm * 37.8);
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

    /** Titre de la signature de la société : le texte saisi, sinon la raison sociale. */
    public static function companySignatureTitle(array $labels, array $company): string
    {
        return $labels['company_signature'] !== '' ? $labels['company_signature'] : ($company['name'] ?? '');
    }

    public static function columns(array $labels): array
    {
        return [
            'number' => $labels['col_number'],
            'description' => $labels['col_description'],
            'quantity' => $labels['col_quantity'],
            'unit_price' => $labels['col_unit_price'],
            'amount' => $labels['col_amount'],
        ];
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
     * Le taux est ajouté aux libellés « Remise » et « TVA » : « TVA (20 %) ».
     */
    public static function totalRows(DocumentTotals $totals, array $labels, ?string $ttcLabel = null): array
    {
        $rows = [];

        if ($totals->hasDiscount()) {
            $rows[] = [$labels['subtotal'], Money::format($totals->subtotal)];
            $rows[] = [trim($labels['discount'].' ('.self::percent($totals->discountPercent).' %)'), '-'.Money::format($totals->discount)];
        }

        $rows[] = [$labels['total_ht'], Money::format($totals->totalHt)];
        $rows[] = [trim($labels['vat'].' ('.$totals->vatRate.' %)'), Money::format($totals->vat)];

        if ($ttcLabel !== null) {
            $rows[] = [$ttcLabel, Money::format($totals->totalTtc, true), 'bold'];
        }

        $rows[] = [$labels['due'], Money::format($totals->totalTtc, true), 'due'];

        return $rows;
    }

    /** "10.00" => "10", "12.50" => "12,5" */
    public static function percent(string $percent): string
    {
        return Quantity::format($percent);
    }
}
