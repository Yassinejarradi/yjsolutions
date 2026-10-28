<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Support\Backup;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

/**
 * Paramètres > Sauvegardes : liste, sauvegarde immédiate et téléchargement.
 */
class BackupController extends Controller
{
    public function index(): View
    {
        return view('settings.backups', ['backups' => Backup::all()]);
    }

    public function store(): RedirectResponse
    {
        try {
            Backup::create();
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('settings.backups')->with('error', 'La sauvegarde a échoué. Réessayez ou contactez la personne qui gère l’application.');
        }

        return redirect()->route('settings.backups')->with('success', 'La sauvegarde a été créée. Vous pouvez la télécharger ci-dessous.');
    }

    public function download(string $name): BinaryFileResponse
    {
        $path = Backup::path($name);
        abort_unless($path, 404);

        return response()->download($path);
    }
}
