<?php

namespace App\Livewire;

use App\Livewire\Concerns\EditsDocumentHeader;
use App\Livewire\Concerns\EditsDocumentLayout;
use App\Livewire\Concerns\EditsDocumentLines;
use App\Models\Client;
use App\Models\Invoice;
use App\Support\AmountInWords;
use App\Support\DocumentSheet;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;
use LogicException;

/**
 * Éditeur « comme Canva » d'une facture brouillon (mêmes morceaux que l'éditeur de devis).
 *
 * Sauvegarde automatique comme pour les devis. « Émettre la facture » attribue le numéro :
 * ensuite la facture n'est plus modifiable.
 */
class InvoiceEditor extends Component
{
    use EditsDocumentHeader, EditsDocumentLayout, EditsDocumentLines;

    public ?int $invoiceId = null;

    public string $dueDate = '';

    public function mount(?Invoice $invoice = null, ?int $preselectedClientId = null): void
    {
        // Sans facture, Livewire injecte un modèle vide : on part alors d'un nouveau brouillon.
        if (! $invoice?->exists) {
            $client = $preselectedClientId ? Client::active()->find($preselectedClientId) : null;
            $invoice = Invoice::newDraft($client);
        }

        if ($invoice->exists && ! $invoice->isDraft()) {
            abort(403, 'Une facture émise ne peut plus être modifiée.');
        }

        $this->invoiceId = $invoice->id;
        $this->clientId = $invoice->client_id;
        $this->issueDate = $invoice->issue_date->format('d/m/Y');
        $this->dueDate = $invoice->due_date->format('d/m/Y');
        $this->reference = $invoice->reference ?? '';
        $this->vatRate = (string) $invoice->vat_rate;
        $this->discountPercent = (float) $invoice->discount_percent ? DocumentSheet::percent((string) $invoice->discount_percent) : '';
        $this->notes = $invoice->notes ?? '';
        $this->savedAt = $invoice->exists ? $invoice->updated_at->format('H:i') : null;

        $this->fillLinesFrom($invoice->exists ? $invoice->lines : []);
        $this->fillLayout($invoice->resolvedLayout(), $invoice->companyDetails());
    }

    /**
     * Enregistre le brouillon. Renvoie false (et affiche les erreurs) si une saisie est invalide.
     */
    public function save(): bool
    {
        $validator = Validator::make($this->all(), $this->draftRules(), $this->lineMessages() + $this->messages());

        if ($validator->fails()) {
            $this->setErrorBag($validator->errors());

            return false;
        }

        $this->resetErrorBag();

        $invoice = $this->invoiceId ? Invoice::findOrFail($this->invoiceId) : new Invoice;

        if ($invoice->exists && ! $invoice->isDraft()) {
            return false;
        }

        $invoice->fill([
            'client_id' => $this->clientId,
            'issue_date' => self::parseDate($this->issueDate),
            'due_date' => self::parseDate($this->dueDate),
            'reference' => trim($this->reference),
            'vat_rate' => (int) $this->vatRate,
            'discount_percent' => $this->parsedDiscount(),
            'notes' => trim($this->notes) === '' ? null : $this->notes,
            'layout' => $this->layoutForStorage() ?: null,
        ]);

        $created = ! $invoice->exists;
        $invoice->replaceLines($this->parsedLines());

        $this->invoiceId = $invoice->id;
        $this->savedAt = now()->format('H:i');

        if ($created) {
            // L'adresse devient celle du brouillon : un rechargement de la page ne perd rien.
            $this->js('history.replaceState(null, "", '.json_encode(route('invoices.show', $invoice)).')');
        }

        return true;
    }

    /**
     * « Émettre la facture » : enregistre, vérifie qu'elle est complète, puis attribue le numéro.
     */
    public function issue(): void
    {
        if (! $this->save() || ! $this->checkReadyToIssue('émettre la facture')) {
            return;
        }

        $invoice = Invoice::findOrFail($this->invoiceId);

        try {
            $invoice->issue();
        } catch (LogicException $e) {
            $this->addError('send', $e->getMessage());

            return;
        }

        session()->flash('success', "La facture {$invoice->number} est émise. Elle n’est plus modifiable.");
        $this->redirectRoute('invoices.show', $invoice);
    }

    /**
     * Le délai de paiement du client fixe l'échéance.
     */
    protected function clientChosen(Client $client): void
    {
        if ($issue = self::parseDate($this->issueDate)) {
            $this->dueDate = $issue->copy()->addDays($client->effectivePaymentTermsDays())->format('d/m/Y');
        }
    }

    public function render(): View
    {
        $totals = $this->currentTotals();
        $last = Invoice::lastIssueDate();

        return view('livewire.invoice-editor', $this->layoutViewData() + $this->clientViewData() + [
            'totals' => $totals,
            'words' => AmountInWords::fromCents($totals->totalTtc),
            'lastIssueDate' => $last?->format('d/m/Y'),
            'tooEarly' => $last && ($issue = self::parseDate($this->issueDate)) && $issue->lt($last),
        ]);
    }

    protected function documentType(): string
    {
        return 'invoice';
    }

    protected function secondDateField(): string
    {
        return 'dueDate';
    }

    protected function currentDocument(): ?object
    {
        return $this->invoiceId ? Invoice::find($this->invoiceId) : null;
    }

    protected function draftRules(): array
    {
        return $this->lineRules() + [
            'clientId' => ['nullable', 'integer', Rule::exists(Client::class, 'id')],
            'issueDate' => ['required', 'date_format:d/m/Y'],
            'dueDate' => ['required', 'date_format:d/m/Y', 'after_or_equal:issueDate'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function messages(): array
    {
        return [
            'issueDate.required' => 'Indiquez la date de facture.',
            'issueDate.date_format' => 'Date au format JJ/MM/AAAA.',
            'dueDate.required' => 'Indiquez la date d’échéance.',
            'dueDate.date_format' => 'Date au format JJ/MM/AAAA.',
            'dueDate.after_or_equal' => 'Ne peut pas être avant la date de facture.',
        ];
    }
}
