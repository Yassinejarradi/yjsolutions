<?php

namespace App\Support;

/**
 * Montants en dirhams, stockés en centimes (entiers).
 *
 * Affichage au format marocain/français : 12 345,67 MAD.
 */
class Money
{
    /**
     * Formate des centimes : 1234567 => "12 345,67" (ou "12 345,67 MAD").
     */
    public static function format(int $cents, bool $withCurrency = false): string
    {
        $sign = $cents < 0 ? '-' : '';
        $abs = abs($cents);

        $formatted = $sign
            .number_format(intdiv($abs, 100), 0, '', ' ')
            .','.str_pad((string) ($abs % 100), 2, '0', STR_PAD_LEFT);

        return $withCurrency ? $formatted.' MAD' : $formatted;
    }

    /**
     * Forme courte pour un graphique, sans centimes : 950 / 12,5 k / 1,25 M.
     */
    public static function compact(int $cents): string
    {
        $dirhams = intdiv($cents, 100);
        $abs = abs($dirhams);

        [$value, $suffix] = match (true) {
            $abs >= 1_000_000 => [$dirhams / 1_000_000, ' M'],
            $abs >= 1_000 => [$dirhams / 1_000, ' k'],
            default => [$dirhams, ''],
        };

        $decimals = $suffix === '' ? 0 : (abs($value) >= 100 ? 0 : (abs($value) >= 10 ? 1 : 2));
        $formatted = number_format($value, $decimals, ',', ' ');
        if ($decimals > 0) {
            $formatted = rtrim(rtrim($formatted, '0'), ',');
        }

        return $formatted.$suffix;
    }

    /**
     * Lit une saisie ("1 234,56", "1234.5"...) et renvoie des centimes, ou null si invalide.
     */
    public static function parse(string|int|null $input): ?int
    {
        $decimal = DecimalInput::parse($input, 2);

        return $decimal?->getUnscaledValue()->toInt();
    }

    /**
     * Centimes => chaîne décimale pour un champ de saisie : 123450 => "1234,50".
     */
    public static function toInput(?int $cents): string
    {
        if ($cents === null) {
            return '';
        }

        $sign = $cents < 0 ? '-' : '';
        $abs = abs($cents);

        return $sign.intdiv($abs, 100).','.str_pad((string) ($abs % 100), 2, '0', STR_PAD_LEFT);
    }
}
