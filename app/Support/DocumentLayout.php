<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Mise en page d'un document : logo, textes, blocs affichés et informations de la société.
 *
 * Trois niveaux, du plus général au plus précis :
 *  1. les valeurs d'origine (defaults) ;
 *  2. le modèle des Paramètres (Setting::document_layouts), modifiable depuis l'éditeur ;
 *  3. les changements propres au document (colonne « layout »).
 *
 * Un brouillon ne garde que ses différences : un changement du modèle s'applique donc aux brouillons
 * qui n'ont pas été personnalisés. À l'envoi, la mise en page complète est figée dans le document.
 *
 * Forme : ['logo' => [mode, path, size], 'labels' => [...], 'show' => [...], 'company' => [...]]
 */
final class DocumentLayout
{
    /** Largeur du logo, en cm (PDF et Word) ; l'écran utilise les mêmes proportions. */
    public const LOGO_SIZES = ['small' => 1.8, 'medium' => 2.4, 'large' => 3.2];

    public const LOGO_MODES = ['default', 'hidden', 'custom'];

    /** Logos propres aux documents : disque privé, servis par la route « document-logo ». */
    public const LOGO_DISK = 'local';

    public const LOGO_DIR = 'document-logos';

    /** Champs de la société modifiables sur un document (null = valeur des Paramètres). */
    public const COMPANY_FIELDS = [
        'name' => 'Raison sociale',
        'address' => 'Adresse',
        'phone' => 'Téléphone',
        'email' => 'Email',
        'ice' => 'ICE',
        'rc' => 'RC',
        'if_number' => 'IF',
        'tp' => 'TP',
        'cnss' => 'CNSS',
    ];

    /** Blocs que l'on peut masquer, par type de document, avec leur nom dans le panneau « Mise en page ». */
    public static function blocks(string $type): array
    {
        $specific = $type === 'invoice'
            ? ['reference' => 'Ligne « N° Bon de commande »', 'words' => 'Montant en lettres', 'payments' => 'Paiements reçus (dans les totaux)', 'bank' => 'Coordonnées bancaires']
            : ['reference' => 'Ligne « Réf. demande »', 'words' => 'Montant en lettres', 'validity' => 'Validité de l’offre', 'payment_terms' => 'Modalités de paiement', 'completion_delay' => 'Délai de réalisation'];

        return ['amount' => 'Montant en haut à droite', 'client_ice' => 'ICE du client'] + $specific + [
            'notes' => 'Mentions',
            'client_signature' => 'Signature du client',
            'company_signature' => 'Signature de la société',
            'footer' => 'Pied de page (société)',
            'page_numbers' => 'Numéro de page',
        ];
    }

    /**
     * Valeurs d'origine pour un type de document : « quote » (devis) ou « invoice » (facture).
     */
    public static function defaults(string $type): array
    {
        return [
            // frozen : logo des Paramètres copié à l'envoi (une copie du document reprend le logo actuel).
            'logo' => ['mode' => 'default', 'path' => null, 'size' => 'medium', 'frozen' => false],
            'labels' => self::defaultLabels($type),
            // Sur une facture, la signature du client n'apparaît pas (comme dans le prototype).
            'show' => array_merge(array_fill_keys(array_keys(self::blocks($type)), true), $type === 'invoice' ? ['client_signature' => false] : []),
            'company' => array_fill_keys(array_keys(self::COMPANY_FIELDS), null),
        ];
    }

    private static function defaultLabels(string $type): array
    {
        $common = [
            'col_number' => '#',
            'col_description' => 'Article & Description',
            'col_quantity' => 'Quantité',
            'col_unit_price' => 'Prix Unitaire',
            'col_amount' => 'Montant',
            'subtotal' => 'Sous-total',
            'discount' => 'Remise',
            'total_ht' => 'Total HT',
            'vat' => 'TVA',
            'client_signature' => 'Bon pour accord – Client',
            'client_signature_note' => '(Date, cachet et signature)',
            'company_signature' => '', // vide : raison sociale de la société
            'company_signature_note' => '(Cachet et signature)',
        ];

        if ($type === 'invoice') {
            return [
                'title' => 'FACTURE',
                'number' => 'N° de facture :',
                'amount' => 'Solde exigible',
                'client' => 'Facturer à',
                'date' => 'Date de facture :',
                'due_date' => 'Date d’échéance :',
                'reference' => 'N° Bon de commande :',
                'total_ttc' => 'Total TTC',
                'paid' => 'Déjà payé',
                'due' => 'Solde exigible',
                'words' => 'Arrêtée la présente facture à la somme de :',
                'bank' => 'Paiement par virement bancaire :',
            ] + $common;
        }

        return [
            'title' => 'DEVIS',
            'number' => 'N° de devis :',
            'amount' => 'Total TTC',
            'client' => 'Devis établi pour',
            'date' => 'Date du devis :',
            'due_date' => 'Valable jusqu’au :',
            'reference' => 'Réf. demande :',
            'due' => 'Net à payer TTC',
            'words' => 'Arrêté le présent devis à la somme de :',
            'validity' => 'Validité de l’offre :',
            'payment_terms' => 'Modalités de paiement :',
            'completion_delay' => 'Délai de réalisation :',
        ] + $common;
    }

    /**
     * Mise en page complète : valeurs d'origine ← modèle des Paramètres ← changements du document.
     */
    public static function resolve(string $type, ?array $stored): array
    {
        return self::sanitize($type, array_replace_recursive(self::base($type), $stored ?? []));
    }

    /**
     * Ce qui diffère de la base (valeurs d'origine + modèle) : c'est ce qu'un brouillon enregistre.
     * Pour la société, la référence est la fiche des Paramètres.
     */
    public static function diff(string $type, array $layout, ?array $companyBase = null): array
    {
        $layout = self::sanitize($type, $layout);
        $base = self::base($type);
        $companyBase ??= Setting::current()->toSnapshot();

        $diff = [];

        foreach (['logo', 'labels', 'show'] as $group) {
            foreach ($layout[$group] as $key => $value) {
                if ($value !== $base[$group][$key]) {
                    $diff[$group][$key] = $value;
                }
            }
        }

        foreach ($layout['company'] as $key => $value) {
            if ($value !== null && $value !== (string) ($companyBase[$key] ?? '')) {
                $diff['company'][$key] = $value;
            }
        }

        return $diff;
    }

    /**
     * Modèle des Paramètres tiré d'une mise en page : logo, textes et blocs qui diffèrent de l'origine.
     * Les informations de la société n'en font pas partie (elles se règlent dans Paramètres › Société).
     */
    public static function templateFrom(string $type, array $layout): array
    {
        $layout = self::sanitize($type, $layout);
        $defaults = self::defaults($type);
        $template = [];

        foreach (['logo', 'labels', 'show'] as $group) {
            foreach ($layout[$group] as $key => $value) {
                if ($value !== $defaults[$group][$key]) {
                    $template[$group][$key] = $value;
                }
            }
        }

        return $template;
    }

    /**
     * Garde uniquement les clés connues, avec des valeurs du bon type (saisie de l'utilisateur).
     */
    public static function sanitize(string $type, array $input): array
    {
        $defaults = self::defaults($type);
        $clean = $defaults;

        $logo = $input['logo'] ?? [];
        $clean['logo']['mode'] = in_array($logo['mode'] ?? null, self::LOGO_MODES, true) ? $logo['mode'] : 'default';
        $clean['logo']['size'] = array_key_exists($logo['size'] ?? '', self::LOGO_SIZES) ? $logo['size'] : 'medium';
        $path = $logo['path'] ?? null;
        $clean['logo']['path'] = is_string($path) && preg_match('#^'.self::LOGO_DIR.'/[A-Za-z0-9]+\.(png|jpe?g)$#', $path) ? $path : null;

        // Le chemin ne compte que pour un logo personnalisé (et un logo personnalisé sans fichier n'en est pas un).
        if ($clean['logo']['mode'] === 'custom' && $clean['logo']['path'] === null) {
            $clean['logo']['mode'] = 'default';
        }
        if ($clean['logo']['mode'] !== 'custom') {
            $clean['logo']['path'] = null;
        }
        $clean['logo']['frozen'] = $clean['logo']['mode'] === 'custom' && (bool) ($logo['frozen'] ?? false);

        foreach ($defaults['labels'] as $key => $default) {
            $value = $input['labels'][$key] ?? $default;
            $clean['labels'][$key] = is_scalar($value) ? Str::limit(trim((string) $value), 150, '') : $default;
        }

        foreach ($defaults['show'] as $key => $default) {
            $clean['show'][$key] = (bool) ($input['show'][$key] ?? $default);
        }

        foreach ($defaults['company'] as $key => $default) {
            $value = $input['company'][$key] ?? null;
            $clean['company'][$key] = is_scalar($value) ? Str::limit(trim((string) $value), 255, '') : null;
        }

        return $clean;
    }

    /**
     * Informations de la société imprimées : celles de $base, remplacées par celles du document.
     */
    public static function company(array $layout, array $base): array
    {
        foreach ($layout['company'] ?? [] as $key => $value) {
            if ($value !== null) {
                $base[$key] = $value;
            }
        }

        return $base;
    }

    /**
     * Fichier du logo à imprimer, ou null s'il est masqué.
     */
    public static function logoFile(array $layout): ?string
    {
        return match ($layout['logo']['mode']) {
            'hidden' => null,
            'custom' => Storage::disk(self::LOGO_DISK)->exists((string) $layout['logo']['path'])
                ? Storage::disk(self::LOGO_DISK)->path($layout['logo']['path'])
                : Setting::current()->logoFilePath(),
            default => Setting::current()->logoFilePath(),
        };
    }

    public static function logoWidthCm(array $layout): float
    {
        return self::LOGO_SIZES[$layout['logo']['size']] ?? self::LOGO_SIZES['medium'];
    }

    /**
     * Range un logo téléversé et renvoie son chemin (« document-logos/xxxx.png »).
     */
    public static function storeLogo(UploadedFile $file): string
    {
        $extension = strtolower($file->extension()) === 'png' ? 'png' : 'jpg';

        return $file->storeAs(self::LOGO_DIR, Str::random(32).'.'.$extension, self::LOGO_DISK);
    }

    /**
     * Copie le logo actuel des Paramètres pour le figer dans un document envoyé.
     */
    public static function freezeLogo(array $layout): array
    {
        if ($layout['logo']['mode'] !== 'default') {
            return $layout;
        }

        $source = Setting::current()->logoFilePath();
        $extension = strtolower(pathinfo($source, PATHINFO_EXTENSION)) === 'png' ? 'png' : 'jpg';
        $path = self::LOGO_DIR.'/'.Str::random(32).'.'.$extension;
        Storage::disk(self::LOGO_DISK)->put($path, (string) file_get_contents($source));

        $layout['logo']['mode'] = 'custom';
        $layout['logo']['path'] = $path;
        $layout['logo']['frozen'] = true;

        return $layout;
    }

    /**
     * Mise en page d'un document envoyé, reprise pour un nouveau brouillon : un logo figé
     * redevient le logo des Paramètres.
     */
    public static function unfreeze(array $layout): array
    {
        if ($layout['logo']['frozen']) {
            $layout['logo'] = ['mode' => 'default', 'path' => null, 'size' => $layout['logo']['size'], 'frozen' => false];
        }

        return $layout;
    }

    /**
     * Valeurs d'origine + modèle des Paramètres (sans la société, qui vient de la fiche Société).
     */
    private static function base(string $type): array
    {
        $template = Setting::current()->document_layouts[$type] ?? [];
        unset($template['company']);

        return array_replace_recursive(self::defaults($type), $template);
    }
}
