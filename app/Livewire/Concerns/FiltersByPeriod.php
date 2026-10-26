<?php

namespace App\Livewire\Concerns;

use DateTime;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Url;

/**
 * Filtre « Période » des listes (devis, factures), gardé dans l'adresse (?periode=mois, ?du=…&au=…).
 */
trait FiltersByPeriod
{
    #[Url(as: 'periode', except: '')]
    public string $period = '';

    #[Url(as: 'du', except: '')]
    public string $from = '';

    #[Url(as: 'au', except: '')]
    public string $to = '';

    /** Périodes proposées. « dates » affiche les champs Du / Au. */
    public static function periods(): array
    {
        return [
            '' => 'Toutes les dates',
            'mois' => 'Ce mois-ci',
            'mois-dernier' => 'Le mois dernier',
            'annee' => 'Cette année',
            'annee-derniere' => 'L’année dernière',
            'dates' => 'Entre deux dates…',
        ];
    }

    /**
     * @return array{0: Carbon|null, 1: Carbon|null}
     */
    protected function periodRange(): array
    {
        $now = today();

        return match ($this->period) {
            'mois' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'mois-dernier' => [$now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->subMonthNoOverflow()->endOfMonth()],
            'annee' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            'annee-derniere' => [$now->copy()->subYear()->startOfYear(), $now->copy()->subYear()->endOfYear()],
            'dates' => [self::parsePeriodDate($this->from), self::parsePeriodDate($this->to)],
            default => [null, null],
        };
    }

    private static function parsePeriodDate(string $value): ?Carbon
    {
        $date = DateTime::createFromFormat('!d/m/Y', trim($value));

        return $date && ! DateTime::getLastErrors() ? Carbon::instance($date) : null;
    }
}
