<?php

namespace App\Models\Concerns;

use App\Models\Client;
use App\Models\Setting;
use App\Support\DocumentLayout;

/**
 * Informations imprimées d'un document (devis, facture) : client, société et mise en page.
 *
 * Brouillon : valeurs actuelles (fiche client, Paramètres, modèle de mise en page).
 * Document émis : copies faites au moment de l'émission (snapshots et mise en page figée).
 *
 * Le modèle doit avoir les colonnes client_snapshot, company_snapshot et layout, et une relation client().
 */
trait HasDocumentSnapshots
{
    /** « quote » ou « invoice » (mise en page et modèle des Paramètres). */
    abstract public function documentType(): string;

    abstract public function isDraft(): bool;

    public function clientDetails(): array
    {
        return $this->client_snapshot ?? $this->client?->toSnapshot() ?? Client::emptySnapshot();
    }

    public function companyDetails(): array
    {
        return $this->company_snapshot ?? DocumentLayout::company($this->resolvedLayout(), Setting::current()->toSnapshot());
    }

    /**
     * Mise en page complète. Brouillon : origine ← modèle des Paramètres ← changements du document.
     * Document émis : celle figée à l'émission (l'origine pour les documents émis avant cette option).
     */
    public function resolvedLayout(): array
    {
        if ($this->isDraft()) {
            return DocumentLayout::resolve($this->documentType(), $this->layout);
        }

        return DocumentLayout::sanitize(
            $this->documentType(),
            array_replace_recursive(DocumentLayout::defaults($this->documentType()), $this->layout ?? []),
        );
    }

    /**
     * Copies à faire au moment de l'émission : client, société (avec les changements du document)
     * et mise en page figée avec son logo.
     */
    protected function snapshotsForIssue(Setting $setting): array
    {
        $layout = $this->resolvedLayout();

        return [
            'client_snapshot' => $this->client->toSnapshot(),
            'company_snapshot' => DocumentLayout::company($layout, $setting->toSnapshot()),
            'layout' => DocumentLayout::freezeLogo($layout),
        ];
    }
}
