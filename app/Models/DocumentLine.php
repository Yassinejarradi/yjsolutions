<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Ligne d'un devis ou d'une facture. Montants en centimes HT.
 *
 * @property string $description
 * @property string $quantity décimal à 3 chiffres, ex. "1.500"
 * @property int|null $unit_price
 * @property int $amount
 */
class DocumentLine extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            // Chaîne "1.500" : jamais de float (SQLite renverrait 1.5).
            'quantity' => 'decimal:3',
            'unit_price' => 'integer',
            'amount' => 'integer',
        ];
    }

    public function document(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Ligne sans désignation ni prix : ignorée à l'impression et supprimée à l'émission.
     */
    public function isBlank(): bool
    {
        return trim($this->description) === '' && $this->unit_price === null;
    }
}
