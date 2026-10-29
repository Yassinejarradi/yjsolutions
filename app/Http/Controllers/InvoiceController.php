<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Http\Requests\PaymentRequest;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Quote;
use App\Support\DocumentSheet;
use App\Support\Money;
use App\Support\Pdf\DocumentPdf;
use App\Support\Word\DocumentWord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use LogicException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Factures : la liste (composant InvoiceList), l'éditeur des brouillons (composant InvoiceEditor),
 * la consultation des factures émises, les paiements, l'annulation, le PDF et l'export Word.
 */
class InvoiceController extends Controller
{
    public function index(): View
    {
        return view('invoices.index');
    }

    /**
     * Nouvelle facture ; ?client=12 (depuis la fiche client) la préremplit avec ce client.
     */
    public function create(Request $request): View
    {
        return view('invoices.edit', ['invoice' => null, 'clientId' => $request->integer('client') ?: null]);
    }

    /**
     * « Préparer la facture (brouillon) » depuis un devis envoyé ou accepté.
     */
    public function fromQuote(Quote $quote): RedirectResponse
    {
        if ($quote->invoice) {
            return redirect()->route('invoices.show', $quote->invoice)->with('success', 'Ce devis a déjà été converti en facture.');
        }

        try {
            $invoice = Invoice::fromQuote($quote);
        } catch (LogicException $e) {
            return redirect()->route('quotes.show', $quote)->with('error', $e->getMessage());
        }

        return redirect()->route('invoices.show', $invoice)
            ->with('success', "Facture brouillon créée à partir du devis {$quote->number}. Vérifiez-la, puis cliquez sur « Émettre la facture ».");
    }

    /**
     * « Facturer ce devis » en un clic : la facture est créée et émise tout de suite, datée d'aujourd'hui.
     * Si l'émission n'est pas possible (client archivé, date…), le brouillon reste ouvert pour être complété.
     */
    public function issueFromQuote(Quote $quote): RedirectResponse
    {
        if ($quote->invoice) {
            return redirect()->route('invoices.show', $quote->invoice)->with('success', 'Ce devis a déjà été facturé.');
        }

        try {
            $invoice = Invoice::fromQuote($quote);
        } catch (LogicException $e) {
            return redirect()->route('quotes.show', $quote)->with('error', $e->getMessage());
        }

        try {
            $invoice->issue();
        } catch (LogicException $e) {
            return redirect()->route('invoices.show', $invoice)
                ->with('error', 'La facture n’a pas pu être émise : '.$e->getMessage().' Complétez le brouillon, puis cliquez sur « Émettre la facture ».');
        }

        return redirect()->route('invoices.show', $invoice)
            ->with('success', "Facture {$invoice->number} émise à partir du devis {$quote->number}.");
    }

    /**
     * Un brouillon s'ouvre dans l'éditeur ; une facture émise s'affiche en lecture seule avec ses paiements.
     */
    public function show(Invoice $invoice): View
    {
        if ($invoice->isDraft()) {
            return view('invoices.edit', ['invoice' => $invoice]);
        }

        return view('invoices.show', [
            'invoice' => $invoice->load('payments', 'quote'),
            'sheet' => DocumentSheet::forInvoice($invoice),
            'methods' => PaymentMethod::cases(),
        ]);
    }

    public function pdf(Invoice $invoice): Response
    {
        $sheet = DocumentSheet::forInvoice($invoice);

        return response(DocumentPdf::render($sheet), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$sheet->fileName.'.pdf"',
        ]);
    }

    public function word(Invoice $invoice): BinaryFileResponse
    {
        $sheet = DocumentSheet::forInvoice($invoice);

        return response()->download(DocumentWord::save($sheet), $sheet->fileName.'.docx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend();
    }

    public function duplicate(Invoice $invoice): RedirectResponse
    {
        $copy = $invoice->duplicate();

        return redirect()->route('invoices.show', $copy)
            ->with('success', 'Nouveau brouillon créé à partir de la facture '.($invoice->number ?? 'brouillon').'.');
    }

    public function cancel(Invoice $invoice): RedirectResponse
    {
        try {
            $invoice->cancel();
        } catch (LogicException $e) {
            return redirect()->route('invoices.show', $invoice)->with('error', $e->getMessage());
        }

        return redirect()->route('invoices.show', $invoice)
            ->with('success', "La facture {$invoice->number} est annulée. Elle garde son numéro ; son PDF porte la mention « ANNULÉE ».");
    }

    /**
     * Seul un brouillon peut être supprimé : une facture émise garde son numéro (numérotation sans trou).
     */
    public function destroy(Invoice $invoice): RedirectResponse
    {
        if (! $invoice->isDraft()) {
            return redirect()->route('invoices.show', $invoice)->with('error', 'Une facture émise ne peut pas être supprimée, seulement annulée.');
        }

        $invoice->lines()->delete();
        $invoice->delete();

        return redirect()->route('invoices.index')->with('success', 'Le brouillon a été supprimé.');
    }

    public function storePayment(PaymentRequest $request, Invoice $invoice): RedirectResponse
    {
        try {
            $payment = $invoice->recordPayment(
                $request->paidOn(),
                $request->amountInCents(),
                PaymentMethod::from($request->validated('method')),
                (string) $request->validated('reference'),
            );
        } catch (LogicException $e) {
            return redirect()->route('invoices.show', $invoice)->with('error', $e->getMessage());
        }

        $invoice->refresh();
        $message = $invoice->balance() === 0
            ? "Paiement de {$this->amount($payment)} enregistré : la facture {$invoice->number} est entièrement payée."
            : "Paiement de {$this->amount($payment)} enregistré. Reste à payer : ".Money::format($invoice->balance(), true).'.';

        return redirect()->route('invoices.show', $invoice)->with('success', $message);
    }

    public function destroyPayment(Invoice $invoice, Payment $payment): RedirectResponse
    {
        try {
            $invoice->removePayment($payment);
        } catch (LogicException $e) {
            return redirect()->route('invoices.show', $invoice)->with('error', $e->getMessage());
        }

        return redirect()->route('invoices.show', $invoice)->with('success', "Le paiement de {$this->amount($payment)} a été supprimé.");
    }

    private function amount(Payment $payment): string
    {
        return Money::format($payment->amount, true);
    }
}
