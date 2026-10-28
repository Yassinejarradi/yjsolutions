<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Enums\QuoteStatus;
use App\Models\Invoice;
use App\Models\Quote;
use App\Support\Revenue;
use Illuminate\View\View;

/**
 * Tableau de bord : chiffre d'affaires, graphique sur 12 mois, points « à suivre » et actions rapides.
 */
class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $today = today();

        return view('dashboard', [
            'monthRevenue' => Revenue::invoiced($today->copy()->startOfMonth(), $today->copy()->endOfMonth()),
            'yearRevenue' => Revenue::invoiced($today->copy()->startOfYear(), $today->copy()->endOfYear()),
            'yearCollected' => Revenue::collected($today->copy()->startOfYear(), $today->copy()->endOfYear()),
            'monthly' => Revenue::monthly(12),
            'overdueCount' => Invoice::query()->withDisplayStatus(InvoiceStatus::Overdue)->count(),
            'pendingQuotes' => Quote::query()->withDisplayStatus(QuoteStatus::Sent)->count(),
        ]);
    }
}
