<?php

namespace App\Support;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

/**
 * Relevé de compte d'un client : ses factures émises (non annulées), ce qui a été payé et le solde dû.
 * Utile pour une relance : le client voit d'un coup d'œil ce qu'il reste à régler.
 */
class ClientStatement
{
    /**
     * @param  list<array{number: string, date: string, due: string, total: string, paid: string, balance: string, late: bool}>  $rows
     */
    public function __construct(
        public Client $client,
        public array $rows,
        public int $total,
        public int $paid,
        public int $balance,
        public array $company,
        public string $logoDataUri,
    ) {}

    public static function for(Client $client): self
    {
        $invoices = $client->invoices()->billed()->orderBy('issue_date')->orderBy('id')->get();
        $setting = Setting::current();
        $logo = $setting->logoFilePath();

        return new self(
            client: $client,
            rows: $invoices->map(fn (Invoice $invoice) => [
                'number' => $invoice->number,
                'date' => $invoice->issue_date->format('d/m/Y'),
                'due' => $invoice->due_date->format('d/m/Y'),
                'total' => Money::format($invoice->total_ttc),
                'paid' => Money::format($invoice->amount_paid),
                'balance' => $invoice->balance() > 0 ? Money::format($invoice->balance()) : 'Payée',
                'late' => $invoice->balance() > 0 && $invoice->due_date->lt(today()),
            ])->all(),
            total: (int) $invoices->sum('total_ttc'),
            paid: (int) $invoices->sum('amount_paid'),
            balance: (int) $invoices->sum(fn (Invoice $invoice) => $invoice->balance()),
            company: $setting->toSnapshot(),
            logoDataUri: 'data:'.(mime_content_type($logo) ?: 'image/png').';base64,'.base64_encode((string) file_get_contents($logo)),
        );
    }

    public function fileName(): string
    {
        return 'Releve_'.Str::slug($this->client->name, '_').'_'.today()->format('Y-m-d');
    }

    public function pdf(): string
    {
        $dompdf = Pdf::loadView('pdf.statement', ['statement' => $this])->setPaper('a4')->getDomPDF();
        $dompdf->render();

        $canvas = $dompdf->getCanvas();
        $metrics = $dompdf->getFontMetrics();
        $font = $metrics->getFont('Carlito') ?? $metrics->getFont('helvetica');
        $width = $metrics->getTextWidth('Page 1 / 1', $font, 8);
        $canvas->page_text(($canvas->get_width() - $width) / 2, $canvas->get_height() - 16, 'Page {PAGE_NUM} / {PAGE_COUNT}', $font, 8, [0.42, 0.45, 0.5]);

        return $dompdf->output();
    }
}
