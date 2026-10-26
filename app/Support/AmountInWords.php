<?php

namespace App\Support;

/**
 * Écrit un montant en toutes lettres, en français (orthographe traditionnelle).
 *
 * Règles appliquées :
 *  - « et » seulement pour 21, 31, 41, 51, 61 et 71 (« vingt et un », « soixante et onze ») ;
 *  - « quatre-vingts » et « deux cents » prennent un s quand rien ne les suit,
 *    sauf devant « mille » qui est invariable (« quatre-vingt mille », « deux cent mille ») ;
 *  - « mille » seul (jamais « un mille ») ; « un million », « deux millions » ;
 *  - « un million de dirhams » quand le nombre finit par million(s) ou milliard(s).
 *
 * Exemple : 10723535 centimes => « Cent sept mille deux cent trente-cinq dirhams et trente-cinq centimes TTC ».
 */
class AmountInWords
{
    private const UNITS = [
        'zéro', 'un', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit', 'neuf',
        'dix', 'onze', 'douze', 'treize', 'quatorze', 'quinze', 'seize', 'dix-sept', 'dix-huit', 'dix-neuf',
    ];

    private const TENS = [2 => 'vingt', 3 => 'trente', 4 => 'quarante', 5 => 'cinquante', 6 => 'soixante'];

    /**
     * Montant complet en dirhams et centimes, première lettre en majuscule.
     */
    public static function fromCents(int $cents, bool $ttc = true): string
    {
        $prefix = $cents < 0 ? 'moins ' : '';
        $cents = abs($cents);

        $dirhams = intdiv($cents, 100);
        $centimes = $cents % 100;

        $text = $prefix.self::number($dirhams);

        // « un million de dirhams », mais « un million cent dirhams ».
        if ($dirhams > 0 && $dirhams % 1_000_000 === 0) {
            $text .= ' de';
        }

        $text .= $dirhams >= 2 ? ' dirhams' : ' dirham';

        if ($centimes > 0) {
            $text .= ' et '.self::number($centimes).($centimes >= 2 ? ' centimes' : ' centime');
        }

        if ($ttc) {
            $text .= ' TTC';
        }

        return mb_strtoupper(mb_substr($text, 0, 1)).mb_substr($text, 1);
    }

    /**
     * Nombre entier positif en lettres (jusqu'à 999 milliards).
     */
    public static function number(int $n): string
    {
        if ($n === 0) {
            return 'zéro';
        }

        $parts = [];

        $billions = intdiv($n, 1_000_000_000);
        $millions = intdiv($n % 1_000_000_000, 1_000_000);
        $thousands = intdiv($n % 1_000_000, 1000);
        $rest = $n % 1000;

        if ($billions > 0) {
            $parts[] = self::belowThousand($billions, true).' milliard'.($billions > 1 ? 's' : '');
        }

        if ($millions > 0) {
            $parts[] = self::belowThousand($millions, true).' million'.($millions > 1 ? 's' : '');
        }

        if ($thousands > 0) {
            // « mille » est invariable et ne prend pas « un » devant.
            $parts[] = $thousands === 1 ? 'mille' : self::belowThousand($thousands, false).' mille';
        }

        if ($rest > 0) {
            $parts[] = self::belowThousand($rest, true);
        }

        return implode(' ', $parts);
    }

    /**
     * 1 à 999. $final = true si rien ne suit (ou si un nom suit : million, milliard),
     * ce qui autorise le pluriel de « cents » et « quatre-vingts ».
     */
    private static function belowThousand(int $n, bool $final): string
    {
        $hundreds = intdiv($n, 100);
        $rest = $n % 100;

        $text = match (true) {
            $hundreds === 0 => '',
            $hundreds === 1 => 'cent',
            default => self::UNITS[$hundreds].' cent'.($rest === 0 && $final ? 's' : ''),
        };

        if ($rest > 0) {
            $tens = self::belowHundred($rest);

            if ($rest === 80 && ! $final) {
                $tens = 'quatre-vingt';
            }

            $text .= ($text === '' ? '' : ' ').$tens;
        }

        return $text;
    }

    /**
     * 1 à 99.
     */
    private static function belowHundred(int $n): string
    {
        if ($n < 20) {
            return self::UNITS[$n];
        }

        if ($n < 70) {
            $tens = intdiv($n, 10);
            $unit = $n % 10;

            return self::TENS[$tens].match ($unit) {
                0 => '',
                1 => ' et un',
                default => '-'.self::UNITS[$unit],
            };
        }

        if ($n < 80) {
            return $n === 71 ? 'soixante et onze' : 'soixante-'.self::UNITS[$n - 60];
        }

        return $n === 80 ? 'quatre-vingts' : 'quatre-vingt-'.self::UNITS[$n - 80];
    }
}
