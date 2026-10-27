<?php

namespace App\Enums;

/**
 * Statuts d'un devis. « Expiré » n'est jamais enregistré : il est calculé
 * (devis envoyé dont la date de validité est passée).
 */
enum QuoteStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Accepted = 'accepted';
    case Refused = 'refused';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Sent => 'Envoyé',
            self::Accepted => 'Accepté',
            self::Refused => 'Refusé',
            self::Expired => 'Expiré',
        };
    }

    /** Classes Tailwind du badge de statut. */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Draft => 'bg-slate-100 text-slate-700 ring-slate-500/20',
            self::Sent => 'bg-blue-50 text-blue-700 ring-blue-600/20',
            self::Accepted => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
            self::Refused => 'bg-red-50 text-red-700 ring-red-600/20',
            self::Expired => 'bg-amber-50 text-amber-800 ring-amber-600/25',
        };
    }

    /** Couleur de la pastille devant le libellé du statut. */
    public function dotClass(): string
    {
        return match ($this) {
            self::Draft => 'bg-slate-400',
            self::Sent => 'bg-blue-500',
            self::Accepted => 'bg-emerald-500',
            self::Refused => 'bg-red-500',
            self::Expired => 'bg-amber-500',
        };
    }
}
