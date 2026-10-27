<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Enums\QuoteStatus;
use App\Models\Invoice;
use App\Models\Quote;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Tableau de bord : accueil, actions rapides et trois chiffres « à suivre ».
 * Les indicateurs complets (CA, graphique sur 12 mois) arrivent en phase 5.
 */
class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('dashboard', [
            'outstanding' => (int) Invoice::query()->billed()->sum(DB::raw('total_ttc - amount_paid')),
            'overdueCount' => Invoice::query()->withDisplayStatus(InvoiceStatus::Overdue)->count(),
            'pendingQuotes' => Quote::query()->withDisplayStatus(QuoteStatus::Sent)->count(),
        ]);
    }
}
