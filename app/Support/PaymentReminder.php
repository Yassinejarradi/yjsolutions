<?php

namespace App\Support;

use App\Models\Invoice;
use App\Models\Setting;

/**
 * Relance d'une facture à payer : message prêt à envoyer et liens « Appeler » / « WhatsApp ».
 * Aucun envoi automatique : l'utilisateur relit le message dans WhatsApp avant de l'envoyer.
 */
class PaymentReminder
{
    /** Factures émises, non soldées, échues ou arrivant à échéance dans ce nombre de jours. */
    public const DAYS_AHEAD = 7;

    /**
     * Numéro au format international pour WhatsApp (chiffres seuls), ou null s'il n'est pas reconnu.
     * « 0661 38 63 48 » → 212661386348 ; « +33 6 12 34 56 78 » → 33612345678.
     */
    public static function internationalNumber(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if (str_starts_with((string) $phone, '+') || str_starts_with($digits, '00')) {
            $digits = ltrim($digits, '0');
        } elseif (strlen($digits) === 10 && $digits[0] === '0') {
            $digits = '212'.substr($digits, 1); // numéro marocain : 06…, 07…, 05…
        } elseif (strlen($digits) === 9 && in_array($digits[0], ['5', '6', '7'], true)) {
            $digits = '212'.$digits;
        }

        return strlen($digits) >= 10 && strlen($digits) <= 15 ? $digits : null;
    }

    /** Lien « Appeler » : le numéro tel quel, sans espaces. */
    public static function telLink(?string $phone): ?string
    {
        $clean = preg_replace('/[^\d+]/', '', (string) $phone);

        return strlen($clean) >= 6 ? 'tel:'.$clean : null;
    }

    /** Lien WhatsApp avec le message ; sans numéro, WhatsApp demande à qui l'envoyer. */
    public static function whatsappLink(Invoice $invoice): string
    {
        $number = self::internationalNumber($invoice->client?->phone);

        return 'https://wa.me/'.($number ?? '').'?text='.rawurlencode(self::message($invoice));
    }

    public static function message(Invoice $invoice): string
    {
        $client = $invoice->client;
        $greeting = $client?->contact_name ? 'Bonjour '.$client->contact_name : 'Bonjour';
        $company = Setting::current()->company_name ?: 'JY SOLUTIONS';
        $balance = Money::format($invoice->balance(), true);
        $total = Money::format($invoice->total_ttc, true);
        $due = $invoice->due_date->format('d/m/Y');

        $state = $invoice->due_date->lt(today())
            ? "arrivée à échéance le {$due}, reste à régler"
            : "arrive à échéance le {$due}";

        $amount = $invoice->amount_paid > 0
            ? "Le montant restant dû est de {$balance} (sur un total de {$total})."
            : "Son montant est de {$balance}.";

        return "{$greeting},\n\n"
            ."Sauf erreur de notre part, notre facture {$invoice->number} du {$invoice->issue_date->format('d/m/Y')} {$state}. {$amount}\n\n"
            ."Nous vous remercions de bien vouloir procéder à son règlement. Si le paiement a déjà été effectué, merci de ne pas tenir compte de ce message.\n\n"
            ."Cordialement,\n{$company}";
    }
}
