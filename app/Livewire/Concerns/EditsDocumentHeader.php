<?php

namespace App\Livewire\Concerns;

use App\Models\Client;
use DateTime;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Haut de la feuille dans l'éditeur (devis, facture) : client, dates, référence, mentions,
 * création rapide d'un client et vérifications avant l'émission.
 */
trait EditsDocumentHeader
{
    public ?int $clientId = null;

    public string $issueDate = '';

    public string $reference = '';

    public string $notes = '';

    /** Heure du dernier enregistrement (« Enregistré à 14:32 »). */
    public ?string $savedAt = null;

    /** Création rapide d'un client sans quitter le document. */
    public bool $showClientForm = false;

    public array $newClient = ['name' => '', 'ice' => '', 'address' => '', 'city' => ''];

    /** Propriété de la deuxième date : « validUntil » (devis) ou « dueDate » (facture). */
    abstract protected function secondDateField(): string;

    /**
     * Garde le même écart entre les deux dates quand la date du document change.
     */
    public function updatingIssueDate(string $value): void
    {
        $field = $this->secondDateField();
        $old = self::parseDate($this->issueDate);
        $new = self::parseDate($value);
        $second = self::parseDate($this->{$field});

        if ($old && $new && $second) {
            $this->{$field} = $new->copy()->addDays((int) $old->diffInDays($second, false))->format('d/m/Y');
        }
    }

    public function chooseClient(int $id): void
    {
        if (Client::selectable($this->clientId)->whereKey($id)->exists()) {
            $this->clientId = $id;
            $this->resetErrorBag('clientId');
            $this->clientChosen(Client::find($id));
            $this->documentChanged();
        }
    }

    /** Après le choix d'un client (la facture recalcule son échéance). */
    protected function clientChosen(Client $client): void {}

    public function openClientForm(): void
    {
        $this->resetErrorBag(['newClient.name', 'newClient.ice', 'newClient.address', 'newClient.city']);
        $this->newClient = ['name' => '', 'ice' => '', 'address' => '', 'city' => ''];
        $this->showClientForm = true;
    }

    /**
     * Création rapide d'un client depuis le document ; il est aussitôt choisi.
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
        $this->clientChosen($client);
        $this->documentChanged();
    }

    /** ICE saisi sur la feuille quand la fiche du client n'en a pas. */
    public string $clientIce = '';

    /**
     * Complète la fiche du client avec son ICE, sans quitter le document.
     */
    public function saveClientIce(): void
    {
        $client = $this->clientId ? Client::find($this->clientId) : null;
        if (! $client) {
            return;
        }

        $this->clientIce = preg_replace('/\s+/', '', $this->clientIce);

        $this->validate([
            'clientIce' => ['required', 'digits:15', Rule::unique(Client::class, 'ice')->ignore($client->id)],
        ], [
            'clientIce.required' => 'Tapez l’ICE du client (15 chiffres).',
            'clientIce.unique' => 'Un autre client a déjà cet ICE.',
        ], [
            'clientIce' => 'ICE',
        ]);

        $client->forceFill(['ice' => $this->clientIce])->save();
        $this->clientIce = '';
    }

    /**
     * Avant l'émission ($action : « envoyer le devis », « émettre la facture ») :
     * un client et au moins une ligne avec une désignation.
     * Renvoie false et affiche les erreurs sinon.
     */
    protected function checkReadyToIssue(string $action): bool
    {
        $validator = Validator::make(
            ['clientId' => $this->clientId, 'lines' => $this->parsedLines()->filter(fn ($l) => $l['description'] !== '')->values()->all()],
            ['clientId' => ['required'], 'lines' => ['required', 'array', 'min:1']],
            [
                'clientId.required' => "Choisissez le client avant d’{$action}.",
                'lines.required' => 'Ajoutez au moins une ligne avec une désignation.',
            ],
        );

        if ($validator->fails()) {
            $this->setErrorBag($validator->errors());

            return false;
        }

        return true;
    }

    /**
     * Données d'affichage : client choisi et clients proposés (actifs, plus le client actuel).
     */
    protected function clientViewData(): array
    {
        return [
            'client' => $this->clientId ? Client::find($this->clientId) : null,
            'clients' => Client::selectable($this->clientId)->get(['id', 'name', 'city', 'ice']),
        ];
    }

    protected static function parseDate(string $value): ?Carbon
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
