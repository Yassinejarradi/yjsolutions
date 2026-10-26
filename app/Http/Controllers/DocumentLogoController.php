<?php

namespace App\Http\Controllers;

use App\Support\DocumentLayout;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Logo propre à un document (téléversé dans l'éditeur, ou figé à l'envoi), rangé hors du dossier public.
 */
class DocumentLogoController extends Controller
{
    public function __invoke(string $file): BinaryFileResponse
    {
        $path = DocumentLayout::LOGO_DIR.'/'.$file;
        $disk = Storage::disk(DocumentLayout::LOGO_DISK);

        abort_unless($disk->exists($path), 404);

        return response()->file($disk->path($path), ['Cache-Control' => 'private, max-age=31536000']);
    }
}
