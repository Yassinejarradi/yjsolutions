<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Enums\QuoteStatus;
use App\Models\Invoice;
use App\Models\Quote;
use Illuminate\View\View;

/**
 * Tableau de bord : accueil, actions rapides et deux chiffres « à suivre ».
 * Les indicateurs complets (CA, graphique sur 12 mois) arrivent en phase 5.
 */
class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('dashboard', [
            'overdueCount' => Invoice::query()->withDisplayStatus(InvoiceStatus::Overdue)->count(),
            'pendingQuotes' => Quote::query()->withDisplayStatus(QuoteStatus::Sent)->count(),
        ]);
    }
}
