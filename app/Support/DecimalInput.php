<?php

namespace App\Support;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

/**
 * Lecture des nombres saisis par l'utilisateur, sans jamais passer par un float.
 *
 * Accepte les formats courants : "1234,5", "1 234,50", "1234.5", "1.234,50", "1,234.50".
 * Si la saisie contient à la fois une virgule et un point, le dernier des deux
 * est le séparateur décimal et l'autre est un séparateur de milliers.
 */
class DecimalInput
{
    /**
     * Convertit une saisie en BigDecimal arrondi à $scale décimales, ou null si la saisie est invalide.
     */
    public static function parse(string|int|null $input, int $scale): ?BigDecimal
    {
        if ($input === null) {
            return null;
        }

        // Retire les espaces (y compris insécables) et les apostrophes de milliers.
        $value = preg_replace("/[\s\x{00A0}\x{202F}']/u", '', (string) $input);

        if ($value === '' || $value === null) {
            return null;
        }

        $lastComma = strrpos($value, ',');
        $lastDot = strrpos($value, '.');

        if ($lastComma !== false && $lastDot !== false) {
            $decimalSeparator = $lastComma > $lastDot ? ',' : '.';
            $thousandsSeparator = $decimalSeparator === ',' ? '.' : ',';
            $value = str_replace($thousandsSeparator, '', $value);
            $value = str_replace($decimalSeparator, '.', $value);
        } else {
            $value = str_replace(',', '.', $value);
        }

        if (! preg_match('/^-?(\d+(\.\d*)?|\.\d+)$/', $value)) {
            return null;
        }

        return BigDecimal::of(rtrim($value, '.'))->toScale($scale, RoundingMode::HalfUp);
    }
}
