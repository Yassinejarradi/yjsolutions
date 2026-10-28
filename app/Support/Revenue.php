<?php

namespace App\Support;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Carbon;

/**
 * Chiffre d'affaires pour le tableau de bord, en centimes.
 *
 * Le CA est le total HT des factures émises et non annulées, à la date de facture.
 * L'encaissé est la somme des paiements reçus, à la date du paiement (TTC).
 */
class Revenue
{
    /** CA HT des factures datées entre deux dates (incluses). */
    public static function invoiced(Carbon $from, Carbon $to): int
    {
        return (int) Invoice::query()->billed()
            ->whereDate('issue_date', '>=', $from)
            ->whereDate('issue_date', '<=', $to)
            ->sum('total_ht');
    }

    /** Paiements reçus entre deux dates (incluses). */
    public static function collected(Carbon $from, Carbon $to): int
    {
        return (int) Payment::query()
            ->whereDate('paid_on', '>=', $from)
            ->whereDate('paid_on', '<=', $to)
            ->sum('amount');
    }

    /**
     * CA HT des derniers mois, mois en cours compris, du plus ancien au plus récent.
     * Regroupé en PHP : même résultat en SQLite et en MySQL.
     *
     * @return list<array{month: Carbon, total: int}>
     */
    public static function monthly(int $months = 12): array
    {
        $start = today()->startOfMonth()->subMonths($months - 1);

        $totals = Invoice::query()->billed()
            ->whereDate('issue_date', '>=', $start)
            ->get(['issue_date', 'total_ht'])
            ->groupBy(fn (Invoice $invoice) => $invoice->issue_date->format('Y-m'))
            ->map(fn ($invoices) => (int) $invoices->sum('total_ht'));

        return collect(range(0, $months - 1))
            ->map(function (int $offset) use ($start, $totals) {
                $month = $start->copy()->addMonths($offset);

                return ['month' => $month, 'total' => $totals->get($month->format('Y-m'), 0)];
            })
            ->all();
    }
}
