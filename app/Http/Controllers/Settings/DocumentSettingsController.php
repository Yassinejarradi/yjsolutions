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

    /**
     * Revient à la mise en page d'origine pour un type de document (« quote »).
     */
    public function resetLayout(string $type): RedirectResponse
    {
        abort_unless(in_array($type, ['quote'], true), 404);

        $setting = Setting::current();
        $layouts = $setting->document_layouts ?? [];
        unset($layouts[$type]);
        $setting->update(['document_layouts' => $layouts ?: null]);

        return redirect()->route('settings.documents')->with('success', 'La mise en page d’origine des devis est rétablie pour les prochains devis.');
    }
}
