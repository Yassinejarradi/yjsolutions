<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\CompanySettingsRequest;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Paramètres > Société : identité légale, coordonnées, logo et banque.
 */
class CompanySettingsController extends Controller
{
    public function edit(): View
    {
        return view('settings.company', ['setting' => Setting::current()]);
    }

    public function update(CompanySettingsRequest $request): RedirectResponse
    {
        $setting = Setting::current();
        // Champs facultatifs vidés : chaîne vide (les colonnes ne sont pas nullables).
        $setting->fill(array_map(fn ($value) => $value ?? '', $request->safe()->except('logo')));

        if ($request->hasFile('logo')) {
            $this->deleteLogo($setting);

            $file = $request->file('logo');
            $setting->logo_path = $file->storeAs('settings', 'logo.'.$file->extension(), Setting::LOGO_DISK);
        }

        $setting->save();

        return redirect()->route('settings.company')->with('success', 'Les informations de la société ont été enregistrées.');
    }

    public function destroyLogo(): RedirectResponse
    {
        $setting = Setting::current();
        $this->deleteLogo($setting);
        $setting->update(['logo_path' => null]);

        return redirect()->route('settings.company')->with('success', 'Le logo d’origine a été rétabli.');
    }

    private function deleteLogo(Setting $setting): void
    {
        if ($setting->logo_path) {
            Storage::disk(Setting::LOGO_DISK)->delete($setting->logo_path);
        }
    }
}
