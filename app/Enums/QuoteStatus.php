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
            self::Draft => 'bg-desk text-ink',
            self::Sent => 'bg-brand-soft text-brand',
            self::Accepted => 'bg-green-100 text-green-800',
            self::Refused => 'bg-red-100 text-red-800',
            self::Expired => 'bg-amber-100 text-amber-800',
        };
    }
}
