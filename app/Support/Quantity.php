<?php

namespace App\Support;

use Brick\Math\BigDecimal;

/**
 * Quantités (3 décimales en base). Affichage sans zéros inutiles : "1.500" => "1,5", "2.000" => "2".
 */
class Quantity
{
    public static function format(string|int|BigDecimal|null $quantity): string
    {
        if ($quantity === null || $quantity === '') {
            return '';
        }

        $decimal = $quantity instanceof BigDecimal ? $quantity : BigDecimal::of((string) $quantity);
        $sign = $decimal->isNegative() ? '-' : '';
        $text = (string) $decimal->abs()->strippedOfTrailingZeros();

        [$integer, $fraction] = array_pad(explode('.', $text, 2), 2, null);
        $integer = number_format((int) $integer, 0, '', ' ');

        return $sign.($fraction === null ? $integer : $integer.','.$fraction);
    }
}
