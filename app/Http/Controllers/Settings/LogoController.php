<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Sert le logo de la société (téléversé dans Paramètres, ou public/logo.png par défaut).
 */
class LogoController extends Controller
{
    public function __invoke(): BinaryFileResponse
    {
        return response()->file(Setting::current()->logoFilePath(), [
            'Cache-Control' => 'no-cache',
        ]);
    }
}
