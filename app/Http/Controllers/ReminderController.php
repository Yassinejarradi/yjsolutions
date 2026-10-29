<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Support\PaymentReminder;
use Illuminate\View\View;

/**
 * « À relancer » : factures en retard et celles qui arrivent à échéance dans les 7 jours.
 */
class ReminderController extends Controller
{
    public function __invoke(): View
    {
        $invoices = Invoice::query()->billed()
            ->whereColumn('amount_paid', '<', 'total_ttc')
            ->whereDate('due_date', '<=', today()->addDays(PaymentReminder::DAYS_AHEAD))
            ->with('client')
            ->orderBy('due_date')
            ->orderBy('id')
            ->get();

        [$overdue, $upcoming] = $invoices->partition(fn (Invoice $invoice) => $invoice->due_date->lt(today()));

        return view('reminders.index', ['overdue' => $overdue, 'upcoming' => $upcoming]);
    }
}
