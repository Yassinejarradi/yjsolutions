<?php

namespace App\Http\Controllers;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Support\DocumentSheet;
use App\Support\Pdf\DocumentPdf;
use App\Support\Word\DocumentWord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Devis : la liste (composant QuoteList), l'éditeur des brouillons (composant QuoteEditor),
 * la consultation des devis envoyés, le PDF, l'export Word et les changements de statut.
 */
class QuoteController extends Controller
{
    public function index(): View
    {
        return view('quotes.index');
    }

    /**
     * Nouveau devis ; ?client=12 (depuis la fiche client) le préremplit avec ce client.
     */
    public function create(Request $request): View
    {
        return view('quotes.edit', ['quote' => null, 'clientId' => $request->integer('client') ?: null]);
    }

    /**
     * Un brouillon s'ouvre dans l'éditeur ; un devis envoyé s'affiche en lecture seule.
     */
    public function show(Quote $quote): View
    {
        if ($quote->isDraft()) {
            return view('quotes.edit', ['quote' => $quote]);
        }

        return view('quotes.show', ['quote' => $quote, 'sheet' => DocumentSheet::forQuote($quote)]);
    }

    /**
     * PDF affiché dans le navigateur (le navigateur propose ensuite de le télécharger ou de l'imprimer).
     */
    public function pdf(Quote $quote): Response
    {
        $sheet = DocumentSheet::forQuote($quote);

        return response(DocumentPdf::render($sheet), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$sheet->fileName.'.pdf"',
        ]);
    }

    public function word(Quote $quote): BinaryFileResponse
    {
        $sheet = DocumentSheet::forQuote($quote);

        return response()->download(DocumentWord::save($sheet), $sheet->fileName.'.docx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend();
    }

    public function duplicate(Quote $quote): RedirectResponse
    {
        $copy = $quote->duplicate();

        return redirect()->route('quotes.show', $copy)
            ->with('success', 'Nouveau brouillon créé à partir du devis '.($quote->number ?? 'brouillon').'.');
    }

    public function accept(Quote $quote): RedirectResponse
    {
        return $this->decide($quote, QuoteStatus::Accepted);
    }

    public function refuse(Quote $quote): RedirectResponse
    {
        return $this->decide($quote, QuoteStatus::Refused);
    }

    /**
     * Seul un brouillon peut être supprimé : un devis envoyé garde son numéro (numérotation sans trou).
     */
    public function destroy(Quote $quote): RedirectResponse
    {
        if (! $quote->isDraft()) {
            return redirect()->route('quotes.show', $quote)->with('error', 'Un devis envoyé ne peut pas être supprimé.');
        }

        $quote->lines()->delete();
        $quote->delete();

        return redirect()->route('quotes.index')->with('success', 'Le brouillon a été supprimé.');
    }

    private function decide(Quote $quote, QuoteStatus $status): RedirectResponse
    {
        if ($quote->status !== QuoteStatus::Sent) {
            return redirect()->route('quotes.show', $quote)->with('error', 'Seul un devis envoyé peut être marqué accepté ou refusé.');
        }

        $status === QuoteStatus::Accepted ? $quote->accept() : $quote->refuse();

        return redirect()->route('quotes.show', $quote)
            ->with('success', "Le devis {$quote->number} est marqué « {$status->label()} ».");
    }
}
