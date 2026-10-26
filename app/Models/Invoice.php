<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\QuoteStatus;
use App\Models\Concerns\HasDocumentLines;
use App\Models\Concerns\HasDocumentSnapshots;
use App\Support\DocumentLayout;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Facture.
 *
 * Brouillon (sans numéro, modifiable) → Émise (numéro attribué, figée) → payée en une ou plusieurs fois.
 * Une facture émise ne se modifie ni ne se supprime : elle peut seulement être annulée (elle garde son numéro).
 * « Partiellement payée », « Payée » et « En retard » sont calculés.
 *
 * @property string|null $number
 * @property InvoiceStatus $status
 * @property Carbon $issue_date
 * @property Carbon $due_date
 * @property int $total_ttc
 * @property int $amount_paid
 */
class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use HasDocumentLines, HasDocumentSnapshots, HasFactory;

    public const SEQUENCE_TYPE = 'invoice';

    protected $guarded = ['id', 'number', 'status', 'amount_paid', 'client_snapshot', 'company_snapshot', 'issued_at', 'cancelled_at'];

    protected $attributes = [
        'status' => 'draft',
        'discount_percent' => '0.00',
        'amount_paid' => 0,
    ];

    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'issue_date' => 'date',
            'due_date' => 'date',
            'vat_rate' => 'integer',
            'discount_percent' => 'decimal:2',
            'subtotal' => 'integer',
            'discount' => 'integer',
            'total_ht' => 'integer',
            'vat' => 'integer',
            'total_ttc' => 'integer',
            'amount_paid' => 'integer',
            'client_snapshot' => 'array',
            'company_snapshot' => 'array',
            'layout' => 'array',
            'issued_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderBy('paid_on')->orderBy('id');
    }

    public function documentType(): string
    {
        return 'invoice';
    }

    /**
     * Nouveau brouillon (non enregistré) : valeurs des Paramètres, échéance selon le délai du client.
     */
    public static function newDraft(?Client $client = null): self
    {
        $setting = Setting::current();

        return new self([
            'client_id' => $client?->id,
            'issue_date' => today(),
            'due_date' => today()->addDays($client?->effectivePaymentTermsDays() ?? $setting->payment_terms_days),
            'vat_rate' => $setting->default_vat_rate,
            'notes' => $setting->invoice_notes,
        ]);
    }

    /**
     * « Convertir en facture » : nouveau brouillon reprenant le client et les lignes du devis.
     * Le devis envoyé passe à « Accepté ».
     */
    public static function fromQuote(Quote $quote): self
    {
        if (! $quote->canBeInvoiced()) {
            throw new LogicException('Ce devis ne peut pas être converti en facture.');
        }

        return DB::transaction(function () use ($quote) {
            $client = $quote->client?->isArchived() ? null : $quote->client;

            $invoice = self::newDraft($client);
            $invoice->fill([
                'quote_id' => $quote->id,
                'vat_rate' => $quote->vat_rate,
                'discount_percent' => $quote->discount_percent,
            ])->save();

            $invoice->replaceLines($quote->lines->map(fn (DocumentLine $line) => [
                'description' => $line->description,
                'quantity' => $line->quantity,
                'unit_price' => $line->unit_price,
            ]));

            if ($quote->status === QuoteStatus::Sent) {
                $quote->accept();
            }

            return $invoice;
        });
    }

    public function isDraft(): bool
    {
        return $this->status === InvoiceStatus::Draft;
    }

    public function isCancelled(): bool
    {
        return $this->status === InvoiceStatus::Cancelled;
    }

    /** Émise et non annulée : on peut y enregistrer des paiements. */
    public function isOpen(): bool
    {
        return $this->status === InvoiceStatus::Issued;
    }

    /** Reste à payer, en centimes. */
    public function balance(): int
    {
        return max(0, $this->total_ttc - $this->amount_paid);
    }

    /**
     * Statut affiché : pour une facture émise, calculé à partir des paiements et de l'échéance.
     */
    public function displayStatus(): InvoiceStatus
    {
        if ($this->status !== InvoiceStatus::Issued) {
            return $this->status;
        }

        return match (true) {
            $this->balance() === 0 => InvoiceStatus::Paid,
            $this->due_date->lt(today()) => InvoiceStatus::Overdue,
            $this->amount_paid > 0 => InvoiceStatus::PartiallyPaid,
            default => InvoiceStatus::Issued,
        };
    }

    /**
     * Filtre sur le statut affiché (y compris les statuts calculés).
     */
    #[Scope]
    protected function withDisplayStatus(Builder $query, InvoiceStatus $status): void
    {
        $issued = fn (Builder $q) => $q->where('status', InvoiceStatus::Issued);
        $unpaid = fn (Builder $q) => $q->whereColumn('amount_paid', '<', 'total_ttc');
        $notDue = fn (Builder $q) => $q->whereDate('due_date', '>=', today());

        match ($status) {
            InvoiceStatus::Paid => $issued($query)->whereColumn('amount_paid', '>=', 'total_ttc'),
            InvoiceStatus::Overdue => $unpaid($issued($query))->whereDate('due_date', '<', today()),
            InvoiceStatus::PartiallyPaid => $notDue($unpaid($issued($query)))->where('amount_paid', '>', 0),
            InvoiceStatus::Issued => $notDue($unpaid($issued($query)))->where('amount_paid', 0),
            default => $query->where('status', $status),
        };
    }

    /** Factures qui comptent dans le chiffre d'affaires et les impayés (émises, non annulées). */
    #[Scope]
    protected function billed(Builder $query): void
    {
        $query->where('status', InvoiceStatus::Issued);
    }

    /**
     * Date de la dernière facture émise : une nouvelle facture ne peut pas être datée avant
     * (sinon les numéros ne suivraient plus l'ordre des dates).
     */
    public static function lastIssueDate(): ?Carbon
    {
        $date = static::query()->whereNotNull('number')->max('issue_date');

        return $date ? Carbon::parse($date) : null;
    }

    /**
     * Émet la facture : numéro suivant (sans trou), copies du client, de la société et de la mise en page.
     */
    public function issue(): void
    {
        DB::transaction(function () {
            // Relit la facture sous verrou : deux émissions simultanées ne peuvent pas numéroter deux fois.
            $fresh = static::query()->lockForUpdate()->findOrFail($this->id);

            if (! $fresh->isDraft()) {
                throw new LogicException('Seul un brouillon peut être émis.');
            }

            if (! $this->client) {
                throw new LogicException('Une facture doit avoir un client pour être émise.');
            }

            $last = static::lastIssueDate();
            if ($last && $this->issue_date->lt($last)) {
                throw new LogicException('La date de facture ne peut pas être antérieure à la dernière facture émise ('.$last->format('d/m/Y').').');
            }

            $setting = Setting::current();
            $year = $this->issue_date->year;

            // Les lignes vides ne sont pas gardées dans un document émis.
            $this->lines()->get()->filter->isBlank()->each->delete();
            $this->unsetRelation('lines');

            $this->forceFill([
                'number' => Quote::formatNumber($setting->invoice_prefix, $year, Sequence::next(self::SEQUENCE_TYPE, $year)),
                'status' => InvoiceStatus::Issued,
                'issued_at' => now(),
            ] + $this->snapshotsForIssue($setting))->save();
        });
    }

    /**
     * Annule une facture émise : elle garde son numéro (pas de trou) et son PDF porte « ANNULÉE ».
     */
    public function cancel(): void
    {
        if (! $this->isOpen()) {
            throw new LogicException('Seule une facture émise peut être annulée.');
        }

        if ($this->amount_paid > 0) {
            throw new LogicException('Cette facture a des paiements : supprimez-les avant de l’annuler.');
        }

        $this->forceFill(['status' => InvoiceStatus::Cancelled, 'cancelled_at' => now()])->save();
    }

    /**
     * Enregistre un paiement (jamais plus que le reste à payer).
     */
    public function recordPayment(Carbon $paidOn, int $amount, PaymentMethod $method, string $reference = ''): Payment
    {
        return DB::transaction(function () use ($paidOn, $amount, $method, $reference) {
            $fresh = static::query()->lockForUpdate()->findOrFail($this->id);

            if (! $fresh->isOpen()) {
                throw new LogicException('Un paiement ne peut être enregistré que sur une facture émise.');
            }

            if ($amount <= 0 || $amount > $fresh->balance()) {
                throw new LogicException('Le montant doit être positif et ne pas dépasser le reste à payer.');
            }

            $payment = $this->payments()->create([
                'paid_on' => $paidOn,
                'amount' => $amount,
                'method' => $method,
                'reference' => trim($reference),
            ]);

            $this->forceFill(['amount_paid' => $fresh->amount_paid + $amount])->save();
            $this->unsetRelation('payments');

            return $payment;
        });
    }

    /**
     * Supprime un paiement saisi par erreur.
     */
    public function removePayment(Payment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $fresh = static::query()->lockForUpdate()->findOrFail($this->id);

            if ($payment->invoice_id !== $this->id || $fresh->isCancelled()) {
                throw new LogicException('Ce paiement ne peut pas être supprimé.');
            }

            $payment->delete();
            $this->forceFill(['amount_paid' => max(0, $fresh->amount_paid - $payment->amount)])->save();
            $this->unsetRelation('payments');
        });
    }

    /**
     * Nouveau brouillon reprenant le contenu de cette facture, daté d'aujourd'hui.
     */
    public function duplicate(): self
    {
        return DB::transaction(function () {
            $client = $this->client?->isArchived() ? null : $this->client;

            $copy = self::newDraft($client);
            $copy->fill([
                'reference' => $this->reference,
                'vat_rate' => $this->vat_rate,
                'discount_percent' => $this->discount_percent,
                'notes' => $this->notes,
                'layout' => DocumentLayout::diff('invoice', DocumentLayout::unfreeze($this->resolvedLayout())),
            ])->save();

            $copy->replaceLines($this->lines->map(fn (DocumentLine $line) => [
                'description' => $line->description,
                'quantity' => $line->quantity,
                'unit_price' => $line->unit_price,
            ]));

            return $copy;
        });
    }

    /**
     * Nom de fichier sans extension : Facture_FAC2026-001, ou Facture_brouillon_12.
     */
    public function fileName(): string
    {
        return $this->number ? 'Facture_'.$this->number : 'Facture_brouillon_'.$this->id;
    }
}
