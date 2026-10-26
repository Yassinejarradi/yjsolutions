<?php

namespace App\Support;

/**
 * Résultat du calcul d'un devis ou d'une facture. Tous les montants sont en centimes.
 */
final readonly class DocumentTotals
{
    /**
     * @param  list<int>  $lineAmounts  montant HT de chaque ligne, dans l'ordre
     */
    public function __construct(
        public array $lineAmounts,
        public int $subtotal,
        public string $discountPercent,
        public int $discount,
        public int $totalHt,
        public int $vatRate,
        public int $vat,
        public int $totalTtc,
    ) {}

    public function hasDiscount(): bool
    {
        return $this->discount !== 0;
    }
}
