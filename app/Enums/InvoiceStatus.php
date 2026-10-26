<?php

namespace App\Enums;

/**
 * Statuts d'une facture. Seuls Draft, Issued et Cancelled sont enregistrés ;
 * PartiallyPaid, Paid et Overdue sont calculés à partir des paiements et de l'échéance.
 */
enum InvoiceStatus: string
{
    case Draft = 'draft';
    case Issued = 'issued';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Issued => 'Émise',
            self::PartiallyPaid => 'Partiellement payée',
            self::Paid => 'Payée',
            self::Overdue => 'En retard',
            self::Cancelled => 'Annulée',
        };
    }

    /** Classes Tailwind du badge de statut. */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Draft => 'bg-desk text-ink',
            self::Issued => 'bg-brand-soft text-brand',
            self::PartiallyPaid => 'bg-amber-100 text-amber-800',
            self::Paid => 'bg-green-100 text-green-800',
            self::Overdue => 'bg-red-100 text-red-800',
            self::Cancelled => 'bg-gray-200 text-gray-600 line-through',
        };
    }
}
