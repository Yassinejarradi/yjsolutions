<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/**
 * Tableau de bord. Les indicateurs (CA, impayés, retards, devis en attente) arrivent en phase 5.
 */
class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('dashboard');
    }
}
