<?php

namespace App\Support;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

/**
 * Calcul des montants d'un devis ou d'une facture. Seule source de vérité :
 * l'éditeur, le PDF, l'export Word et les tests utilisent tous cette classe.
 *
 * Règles (montants en centimes, arrondi au plus proche, 0,5 vers le haut) :
 *  1. montant de ligne = quantité × prix unitaire, arrondi au centime ;
 *  2. sous-total = somme des lignes ;
 *  3. remise = sous-total × % de remise, arrondie ; total HT = sous-total − remise ;
 *  4. TVA = total HT × taux, arrondie ; total TTC = total HT + TVA.
 */
class DocumentCalculator
{
    /** Taux de TVA autorisés au Maroc. */
    public const VAT_RATES = [20, 14, 10, 7, 0];

    /**
     * @param  iterable<array{quantity: string|int|BigDecimal|null, unit_price: int|null}>  $lines
     */
    public static function calculate(iterable $lines, int $vatRate, string|int|BigDecimal|null $discountPercent = null): DocumentTotals
    {
        $lineAmounts = [];

        foreach ($lines as $line) {
            $lineAmounts[] = self::lineAmount($line['quantity'] ?? null, $line['unit_price'] ?? null);
        }

        $subtotal = array_sum($lineAmounts);

        $percent = self::toDecimal($discountPercent, 2);
        $discount = self::percentOf($subtotal, $percent);
        $totalHt = $subtotal - $discount;

        $vat = self::percentOf($totalHt, BigDecimal::of($vatRate));

        return new DocumentTotals(
            lineAmounts: $lineAmounts,
            subtotal: $subtotal,
            discountPercent: (string) $percent,
            discount: $discount,
            totalHt: $totalHt,
            vatRate: $vatRate,
            vat: $vat,
            totalTtc: $totalHt + $vat,
        );
    }

    /**
     * Montant HT d'une ligne, en centimes.
     */
    public static function lineAmount(string|int|BigDecimal|null $quantity, ?int $unitPrice): int
    {
        if ($unitPrice === null) {
            return 0;
        }

        return self::toDecimal($quantity, 3)
            ->multipliedBy($unitPrice)
            ->toScale(0, RoundingMode::HalfUp)
            ->toInt();
    }

    /**
     * Quantité telle qu'enregistrée en base (3 décimales) : "1,5" => "1.500". Vide ou invalide => "0.000".
     */
    public static function normalizeQuantity(string|int|BigDecimal|null $quantity): string
    {
        return (string) self::toDecimal($quantity, 3);
    }

    private static function percentOf(int $cents, BigDecimal $percent): int
    {
        return BigDecimal::of($cents)
            ->multipliedBy($percent)
            ->dividedBy(100, 0, RoundingMode::HalfUp)
            ->toInt();
    }

    private static function toDecimal(string|int|BigDecimal|null $value, int $scale): BigDecimal
    {
        if ($value instanceof BigDecimal) {
            return $value->toScale($scale, RoundingMode::HalfUp);
        }

        return DecimalInput::parse($value, $scale) ?? BigDecimal::zero()->toScale($scale);
    }
}
