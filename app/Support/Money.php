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
