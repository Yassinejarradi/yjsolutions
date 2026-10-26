<?php

namespace App\Models;

use App\Enums\QuoteStatus;
use App\Models\Concerns\HasDocumentLines;
use App\Support\DocumentLayout;
use Database\Factories\QuoteFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Devis.
 *
 * Brouillon (sans numéro, modifiable) → Envoyé (numéro attribué, figé) → Accepté ou Refusé.
 * « Expiré » est calculé : envoyé et date de validité dépassée.
 *
 * @property string|null $number
 * @property QuoteStatus $status
 * @property Carbon $issue_date
 * @property Carbon $valid_until
 * @property int $vat_rate
 * @property string $discount_percent
 * @property int $total_ttc
 * @property array|null $client_snapshot
 * @property array|null $company_snapshot
 */
class Quote extends Model
{
    /** @use HasFactory<QuoteFactory> */
    use HasDocumentLines, HasFactory;

    /** Type utilisé pour la table sequences. */
    public const SEQUENCE_TYPE = 'quote';

    protected $guarded = ['id', 'number', 'status', 'client_snapshot', 'company_snapshot', 'sent_at', 'decided_at'];

    protected $attributes = [
        'status' => 'draft',
        'discount_percent' => '0.00',
    ];

    protected function casts(): array
    {
        return [
            'status' => QuoteStatus::class,
            'issue_date' => 'date',
            'valid_until' => 'date',
            'vat_rate' => 'integer',
            'discount_percent' => 'decimal:2',
            'subtotal' => 'integer',
            'discount' => 'integer',
            'total_ht' => 'integer',
            'vat' => 'integer',
            'total_ttc' => 'integer',
            'client_snapshot' => 'array',
            'company_snapshot' => 'array',
            'layout' => 'array',
            'sent_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Nouveau brouillon (non enregistré) avec les valeurs par défaut des Paramètres.
     */
    public static function newDraft(): self
    {
        $setting = Setting::current();

        return new self([
            'issue_date' => today(),
            'valid_until' => today()->addDays($setting->quote_validity_days),
            'vat_rate' => $setting->default_vat_rate,
            'payment_terms' => $setting->quote_payment_terms,
            'completion_delay' => $setting->quote_completion_delay,
            'notes' => $setting->quote_notes,
        ]);
    }

    public function isDraft(): bool
    {
        return $this->status === QuoteStatus::Draft;
    }

    /**
     * Statut affiché : « Expiré » remplace « Envoyé » après la date de validité.
     */
    public function displayStatus(): QuoteStatus
    {
        if ($this->status === QuoteStatus::Sent && $this->valid_until->lt(today())) {
            return QuoteStatus::Expired;
        }

        return $this->status;
    }

    /**
     * Filtre sur le statut affiché (y compris « Expiré », qui n'est pas enregistré).
     */
    #[Scope]
    protected function withDisplayStatus(Builder $query, QuoteStatus $status): void
    {
        match ($status) {
            QuoteStatus::Sent => $query->where('status', QuoteStatus::Sent)->whereDate('valid_until', '>=', today()),
            QuoteStatus::Expired => $query->where('status', QuoteStatus::Sent)->whereDate('valid_until', '<', today()),
            default => $query->where('status', $status),
        };
    }

    /**
     * Passe le devis à « Envoyé » : attribue le numéro suivant (sans trou) et copie
     * les informations du client et de la société, dans une seule transaction.
     */
    public function send(): void
    {
        DB::transaction(function () {
            // Relit le devis sous verrou : deux envois simultanés ne peuvent pas numéroter deux fois.
            $fresh = static::query()->lockForUpdate()->findOrFail($this->id);

            if (! $fresh->isDraft()) {
                throw new LogicException('Seul un brouillon peut être envoyé.');
            }

            if (! $this->client) {
                throw new LogicException('Un devis doit avoir un client pour être envoyé.');
            }

            $setting = Setting::current();
            $layout = $this->resolvedLayout();
            $year = $this->issue_date->year;

            // Les lignes vides ne sont pas gardées dans un document émis.
            $this->lines()->get()->filter->isBlank()->each->delete();
            $this->unsetRelation('lines');

            $this->forceFill([
                'number' => self::formatNumber($setting->quote_prefix, $year, Sequence::next(self::SEQUENCE_TYPE, $year)),
                'status' => QuoteStatus::Sent,
                'sent_at' => now(),
                'client_snapshot' => $this->client->toSnapshot(),
                'company_snapshot' => DocumentLayout::company($layout, $setting->toSnapshot()),
                // Mise en page complète figée, avec une copie du logo : modifier les Paramètres ne la change plus.
                'layout' => DocumentLayout::freezeLogo($layout),
            ])->save();
        });
    }

    public function accept(): void
    {
        $this->decide(QuoteStatus::Accepted);
    }

    public function refuse(): void
    {
        $this->decide(QuoteStatus::Refused);
    }

    private function decide(QuoteStatus $status): void
    {
        if ($this->status !== QuoteStatus::Sent) {
            throw new LogicException('Seul un devis envoyé peut être accepté ou refusé.');
        }

        $this->forceFill(['status' => $status, 'decided_at' => now()])->save();
    }

    /**
     * Nouveau brouillon reprenant le contenu de ce devis, daté d'aujourd'hui.
     * Le client n'est repris que s'il n'est pas archivé.
     */
    public function duplicate(): self
    {
        $setting = Setting::current();

        return DB::transaction(function () use ($setting) {
            $copy = new self([
                'client_id' => $this->client?->isArchived() ? null : $this->client_id,
                'issue_date' => today(),
                'valid_until' => today()->addDays($setting->quote_validity_days),
                'reference' => $this->reference,
                'vat_rate' => $this->vat_rate,
                'discount_percent' => $this->discount_percent,
                'payment_terms' => $this->payment_terms,
                'completion_delay' => $this->completion_delay,
                'notes' => $this->notes,
                'layout' => DocumentLayout::diff('quote', DocumentLayout::unfreeze($this->resolvedLayout())),
            ]);
            $copy->save();

            $copy->replaceLines($this->lines->map(fn (DocumentLine $line) => [
                'description' => $line->description,
                'quantity' => $line->quantity,
                'unit_price' => $line->unit_price,
            ]));

            return $copy;
        });
    }

    /**
     * DEV + 2026 + -001
     */
    public static function formatNumber(string $prefix, int $year, int $number): string
    {
        return $prefix.$year.'-'.str_pad((string) $number, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Informations du client imprimées : le snapshot si le devis est envoyé, sinon la fiche client actuelle.
     */
    public function clientDetails(): array
    {
        return $this->client_snapshot ?? $this->client?->toSnapshot() ?? Client::emptySnapshot();
    }

    /**
     * Informations de la société imprimées : le snapshot si le devis est envoyé, sinon les Paramètres actuels.
     */
    public function companyDetails(): array
    {
        return $this->company_snapshot ?? DocumentLayout::company($this->resolvedLayout(), Setting::current()->toSnapshot());
    }

    /**
     * Mise en page complète. Brouillon : origine ← modèle des Paramètres ← changements du devis.
     * Devis envoyé : celle figée à l'envoi (seulement l'origine pour les devis envoyés avant cette option).
     */
    public function resolvedLayout(): array
    {
        if ($this->isDraft()) {
            return DocumentLayout::resolve('quote', $this->layout);
        }

        return DocumentLayout::sanitize('quote', array_replace_recursive(DocumentLayout::defaults('quote'), $this->layout ?? []));
    }

    /**
     * Nom de fichier sans extension : Devis_DEV2026-001, ou Devis_brouillon_12.
     */
    public function fileName(): string
    {
        return $this->number ? 'Devis_'.$this->number : 'Devis_brouillon_'.$this->id;
    }
}
