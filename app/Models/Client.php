<?php

namespace App\Models;

use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Client de JY Solutions.
 *
 * Archivé (archived_at rempli) : il n'est plus proposé pour un nouveau document,
 * mais reste visible sur ses anciens documents. Pas de scope global : un client archivé
 * reste accessible par son identifiant.
 *
 * @property string $name
 * @property string|null $ice
 * @property int|null $payment_terms_days
 * @property Carbon|null $archived_at
 */
class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory;

    protected $guarded = ['id', 'archived_at'];

    protected function casts(): array
    {
        return [
            'payment_terms_days' => 'integer',
            'archived_at' => 'datetime',
        ];
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->whereNull('archived_at');
    }

    #[Scope]
    protected function archived(Builder $query): void
    {
        $query->whereNotNull('archived_at');
    }

    /**
     * Clients proposés dans le choix du client d'un document : les clients actifs,
     * plus le client déjà choisi même s'il a été archivé depuis.
     */
    #[Scope]
    protected function selectable(Builder $query, ?int $currentClientId = null): void
    {
        $query->where(fn (Builder $q) => $q->whereNull('archived_at')
            ->when($currentClientId, fn (Builder $q) => $q->orWhere('id', $currentClientId)))
            ->orderBy('name');
    }

    /**
     * Recherche sur la raison sociale, l'ICE, la ville, le contact, l'email et le téléphone.
     */
    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $like = '%'.$term.'%';

        $query->where(fn (Builder $q) => $q
            ->where('name', 'like', $like)
            ->orWhere('ice', 'like', $like)
            ->orWhere('city', 'like', $like)
            ->orWhere('contact_name', 'like', $like)
            ->orWhere('email', 'like', $like)
            ->orWhere('phone', 'like', $like));
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    public function archive(): void
    {
        $this->forceFill(['archived_at' => now()])->save();
    }

    public function restore(): void
    {
        $this->forceFill(['archived_at' => null])->save();
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    /**
     * Le client a-t-il des devis ou des factures ? Si oui, il ne peut être qu'archivé.
     *
     * Phase 4 : ajouter || $this->invoices()->exists().
     */
    public function hasDocuments(): bool
    {
        return $this->quotes()->exists();
    }

    /**
     * Informations copiées dans un document au moment de son émission.
     *
     * @return array{name: string, address: string, ice: string, if_number: string, rc: string}
     */
    public function toSnapshot(): array
    {
        return [
            'name' => $this->name,
            'address' => $this->fullAddress(),
            'ice' => (string) $this->ice,
            'if_number' => $this->if_number,
            'rc' => $this->rc,
        ];
    }

    /**
     * Bloc client d'un brouillon dont le client n'est pas encore choisi.
     */
    public static function emptySnapshot(): array
    {
        return ['name' => '', 'address' => '', 'ice' => '', 'if_number' => '', 'rc' => ''];
    }

    /**
     * Délai de paiement à appliquer : celui du client, sinon celui des Paramètres.
     */
    public function effectivePaymentTermsDays(): int
    {
        return $this->payment_terms_days ?? Setting::current()->payment_terms_days;
    }

    /**
     * Adresse sur une ligne, telle qu'imprimée sur les documents : « Adresse – Ville ».
     */
    public function fullAddress(): string
    {
        return collect([$this->address, $this->city])->filter()->implode(' – ');
    }
}
