<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\DocumentSettingsRequest;
use App\Models\Setting;
use App\Support\DocumentCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Paramètres > Devis et factures : numérotation, TVA par défaut, délais et mentions.
 */
class DocumentSettingsController extends Controller
{
    public function edit(): View
    {
        return view('settings.documents', [
            'setting' => Setting::current(),
            'vatRates' => DocumentCalculator::VAT_RATES,
        ]);
    }

    public function update(DocumentSettingsRequest $request): RedirectResponse
    {
        Setting::current()->update($request->validated());

        return redirect()->route('settings.documents')->with('success', 'Les paramètres des devis et factures ont été enregistrés.');
    }
}
