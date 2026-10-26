<?php

namespace App\Livewire;

use App\Livewire\Concerns\EditsDocumentLines;
use App\Models\Client;
use App\Models\Quote;
use App\Models\Setting;
use App\Support\AmountInWords;
use App\Support\DocumentSheet;
use DateTime;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;
use LogicException;

/**
 * Éditeur « comme Canva » d'un devis brouillon : la page A4 se modifie directement à l'écran.
 *
 * Le navigateur appelle save() quelques secondes après la dernière modification
 * (sauvegarde automatique). Un nouveau devis n'est créé en base qu'au premier enregistrement.
 */
class QuoteEditor extends Component
{
    use EditsDocumentLines;

    public ?int $quoteId = null;

    public ?int $clientId = null;

    public string $issueDate = '';

    public string $validUntil = '';

    public string $reference = '';

    public string $paymentTerms = '';

    public string $completionDelay = '';

    public string $notes = '';

    /** Heure du dernier enregistrement (« Enregistré à 14:32 »). */
    public ?string $savedAt = null;

    /** Création rapide d'un client sans quitter le devis. */
    public bool $showClientForm = false;

    public array $newClient = ['name' => '', 'ice' => '', 'address' => '', 'city' => ''];

    public function mount(?Quote $quote = null, ?int $preselectedClientId = null): void
    {
        // Sans devis, Livewire injecte un modèle vide : on part alors d'un nouveau brouillon.
        if (! $quote?->exists) {
            $quote = Quote::newDraft();
        }

        if ($quote->exists && ! $quote->isDraft()) {
            abort(403, 'Un devis envoyé ne peut plus être modifié.');
        }

        // Depuis la fiche client : seulement un client actif.
        if (! $quote->exists && $preselectedClientId && Client::active()->whereKey($preselectedClientId)->exists()) {
            $quote->client_id = $preselectedClientId;
        }

        $this->quoteId = $quote->id;
        $this->clientId = $quote->client_id;
        $this->issueDate = $quote->issue_date->format('d/m/Y');
        $this->validUntil = $quote->valid_until->format('d/m/Y');
        $this->reference = $quote->reference ?? '';
        $this->vatRate = (string) $quote->vat_rate;
        $this->discountPercent = (float) $quote->discount_percent ? DocumentSheet::percent((string) $quote->discount_percent) : '';
        $this->paymentTerms = $quote->payment_terms ?? '';
        $this->completionDelay = $quote->completion_delay ?? '';
        $this->notes = $quote->notes ?? '';
        $this->savedAt = $quote->exists ? $quote->updated_at->format('H:i') : null;

        $this->fillLinesFrom($quote->exists ? $quote->lines : []);
    }

    /**
     * Garde la même durée de validité quand la date du devis change.
     */
    public function updatingIssueDate(string $value): void
    {
        $old = self::parseDate($this->issueDate);
        $new = self::parseDate($value);
        $until = self::parseDate($this->validUntil);

        if ($old && $new && $until) {
            $this->validUntil = $new->copy()->addDays((int) $old->diffInDays($until, false))->format('d/m/Y');
        }
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

        $quote = $this->quoteId ? Quote::findOrFail($this->quoteId) : new Quote;

        if ($quote->exists && ! $quote->isDraft()) {
            return false;
        }

        $quote->fill([
            'client_id' => $this->clientId,
            'issue_date' => self::parseDate($this->issueDate),
            'valid_until' => self::parseDate($this->validUntil),
            'reference' => trim($this->reference),
            'vat_rate' => (int) $this->vatRate,
            'discount_percent' => $this->parsedDiscount(),
            'payment_terms' => trim($this->paymentTerms),
            'completion_delay' => trim($this->completionDelay),
            'notes' => trim($this->notes) === '' ? null : $this->notes,
        ]);

        $created = ! $quote->exists;
        $quote->replaceLines($this->parsedLines());

        $this->quoteId = $quote->id;
        $this->savedAt = now()->format('H:i');

        if ($created) {
            // L'adresse devient celle du brouillon : un rechargement de la page ne perd rien.
            $this->js('history.replaceState(null, "", '.json_encode(route('quotes.show', $quote)).')');
        }

        return true;
    }

    /**
     * « Marquer comme envoyé » : enregistre, vérifie que le devis est complet, puis attribue le numéro.
     */
    public function send(): void
    {
        if (! $this->save()) {
            return;
        }

        $validator = Validator::make(
            ['clientId' => $this->clientId, 'lines' => $this->parsedLines()->filter(fn ($l) => $l['description'] !== '')->values()->all()],
            ['clientId' => ['required'], 'lines' => ['required', 'array', 'min:1']],
            ['clientId.required' => 'Choisissez le client avant d’envoyer le devis.', 'lines.required' => 'Ajoutez au moins une ligne avec une désignation.'],
        );

        if ($validator->fails()) {
            $this->setErrorBag($validator->errors());

            return;
        }

        $quote = Quote::findOrFail($this->quoteId);

        try {
            $quote->send();
        } catch (LogicException $e) {
            $this->addError('send', $e->getMessage());

            return;
        }

        session()->flash('success', "Le devis {$quote->number} est enregistré comme envoyé. Il n’est plus modifiable.");
        $this->redirectRoute('quotes.show', $quote);
    }

    public function openClientForm(): void
    {
        $this->resetErrorBag(['newClient.name', 'newClient.ice', 'newClient.address', 'newClient.city']);
        $this->newClient = ['name' => '', 'ice' => '', 'address' => '', 'city' => ''];
        $this->showClientForm = true;
    }

    /**
     * Création rapide d'un client depuis le devis ; il est aussitôt choisi.
     */
    public function createClient(): void
    {
        $this->newClient['ice'] = preg_replace('/\s+/', '', (string) $this->newClient['ice']);

        $data = $this->validate([
            'newClient.name' => ['required', 'string', 'max:255'],
            'newClient.ice' => ['nullable', 'digits:15', Rule::unique(Client::class, 'ice')],
            'newClient.address' => ['nullable', 'string', 'max:255'],
            'newClient.city' => ['nullable', 'string', 'max:100'],
        ], [
            'newClient.ice.unique' => 'Un autre client a déjà cet ICE.',
        ], [
            'newClient.name' => 'raison sociale',
            'newClient.ice' => 'ICE',
            'newClient.address' => 'adresse',
            'newClient.city' => 'ville',
        ])['newClient'];

        $client = Client::create([
            'name' => trim($data['name']),
            'ice' => $data['ice'] ?: null,
            'address' => trim((string) $data['address']),
            'city' => trim((string) $data['city']),
        ]);

        $this->clientId = $client->id;
        $this->showClientForm = false;
        $this->documentChanged();
    }

    public function chooseClient(int $id): void
    {
        if (Client::selectable($this->clientId)->whereKey($id)->exists()) {
            $this->clientId = $id;
            $this->resetErrorBag('clientId');
            $this->documentChanged();
        }
    }

    public function render(): View
    {
        $totals = $this->currentTotals();
        $issue = self::parseDate($this->issueDate);
        $until = self::parseDate($this->validUntil);

        return view('livewire.quote-editor', [
            'totals' => $totals,
            'words' => AmountInWords::fromCents($totals->totalTtc),
            'client' => $this->clientId ? Client::find($this->clientId) : null,
            'clients' => Client::selectable($this->clientId)->get(['id', 'name', 'city', 'ice']),
            'company' => Setting::current()->toSnapshot(),
            'validityDays' => $issue && $until ? (int) $issue->diffInDays($until, false) : null,
        ]);
    }

    protected function currentDocument(): ?object
    {
        return $this->quoteId ? Quote::find($this->quoteId) : null;
    }

    protected function draftRules(): array
    {
        return $this->lineRules() + [
            'clientId' => ['nullable', 'integer', Rule::exists(Client::class, 'id')],
            'issueDate' => ['required', 'date_format:d/m/Y'],
            'validUntil' => ['required', 'date_format:d/m/Y', 'after_or_equal:issueDate'],
            'reference' => ['nullable', 'string', 'max:100'],
            'paymentTerms' => ['nullable', 'string', 'max:255'],
            'completionDelay' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function messages(): array
    {
        return [
            'issueDate.required' => 'Indiquez la date du devis.',
            'issueDate.date_format' => 'Date au format JJ/MM/AAAA.',
            'validUntil.required' => 'Indiquez la date de validité.',
            'validUntil.date_format' => 'Date au format JJ/MM/AAAA.',
            'validUntil.after_or_equal' => 'Ne peut pas être avant la date du devis.',
        ];
    }

    private static function parseDate(string $value): ?Carbon
    {
        $value = trim($value);

        if (! preg_match('#^\d{1,2}/\d{1,2}/\d{4}$#', $value)) {
            return null;
        }

        // Refuse les dates impossibles (31/02/2026) au lieu de les reporter au mois suivant.
        $date = DateTime::createFromFormat('!d/m/Y', $value);
        $errors = DateTime::getLastErrors();

        return $date && ! $errors ? Carbon::instance($date) : null;
    }
}
