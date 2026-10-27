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
            self::Draft => 'bg-slate-100 text-slate-700 ring-slate-500/20',
            self::Issued => 'bg-blue-50 text-blue-700 ring-blue-600/20',
            self::PartiallyPaid => 'bg-amber-50 text-amber-800 ring-amber-600/25',
            self::Paid => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
            self::Overdue => 'bg-red-50 text-red-700 ring-red-600/20',
            self::Cancelled => 'bg-slate-100 text-slate-500 ring-slate-400/30 line-through',
        };
    }

    /** Couleur de la pastille devant le libellé du statut. */
    public function dotClass(): string
    {
        return match ($this) {
            self::Draft => 'bg-slate-400',
            self::Issued => 'bg-blue-500',
            self::PartiallyPaid => 'bg-amber-500',
            self::Paid => 'bg-emerald-500',
            self::Overdue => 'bg-red-500',
            self::Cancelled => 'bg-slate-400',
        };
    }
}
