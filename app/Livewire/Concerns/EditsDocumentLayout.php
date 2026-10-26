<?php

namespace App\Livewire\Concerns;

use App\Models\Setting;
use App\Support\DocumentLayout;
use Livewire\WithFileUploads;

/**
 * Mise en page d'un document dans l'éditeur (devis, et facture en phase 4) :
 * logo, textes, blocs affichés et informations de la société (voir App\Support\DocumentLayout).
 *
 * $layout est toujours complet ; seules ses différences sont enregistrées avec le document.
 * Les informations de la société y sont préremplies avec celles des Paramètres.
 */
trait EditsDocumentLayout
{
    use WithFileUploads;

    public array $layout = [];

    /** Logo choisi dans le panneau « Mise en page ». */
    public $logoUpload = null;

    /** Confirmation affichée dans le panneau (« Mise en page enregistrée comme modèle »). */
    public ?string $layoutMessage = null;

    /** « quote » ou « invoice ». */
    abstract protected function documentType(): string;

    /**
     * @param  array  $resolved  mise en page complète du document
     */
    protected function fillLayout(array $resolved, array $company): void
    {
        $resolved['company'] = array_intersect_key($company, DocumentLayout::COMPANY_FIELDS);
        $this->layout = $resolved;
    }

    /**
     * Différences à enregistrer avec le document.
     */
    protected function layoutForStorage(): array
    {
        return DocumentLayout::diff($this->documentType(), $this->layout);
    }

    public function updatedLogoUpload(): void
    {
        $this->validate(
            ['logoUpload' => ['image', 'mimes:png,jpg,jpeg', 'max:2048']],
            ['logoUpload.*' => 'Choisissez une image PNG ou JPG de 2 Mo maximum.'],
        );

        $this->layout['logo'] = [
            'mode' => 'custom',
            'path' => DocumentLayout::storeLogo($this->logoUpload),
            'size' => $this->layout['logo']['size'],
            'frozen' => false,
        ];
        $this->logoUpload = null;
        $this->documentChanged();
    }

    /** « hidden » (retirer le logo) ou « default » (logo de la société). */
    public function setLogoMode(string $mode): void
    {
        if (in_array($mode, ['hidden', 'default'], true)) {
            $this->layout['logo'] = ['mode' => $mode, 'path' => null, 'size' => $this->layout['logo']['size'], 'frozen' => false];
            $this->documentChanged();
        }
    }

    public function setLogoSize(string $size): void
    {
        if (array_key_exists($size, DocumentLayout::LOGO_SIZES)) {
            $this->layout['logo']['size'] = $size;
            $this->documentChanged();
        }
    }

    /**
     * Reprend les informations de la société des Paramètres.
     */
    public function resetCompany(): void
    {
        $this->layout['company'] = array_intersect_key(Setting::current()->toSnapshot(), DocumentLayout::COMPANY_FIELDS);
        $this->documentChanged();
    }

    /**
     * Revient au modèle des Paramètres (logo, textes, blocs et société).
     */
    public function resetLayout(): void
    {
        $this->fillLayout(DocumentLayout::resolve($this->documentType(), null), Setting::current()->toSnapshot());
        $this->layoutMessage = 'La mise en page par défaut est rétablie sur ce document.';
        $this->documentChanged();
    }

    /**
     * La mise en page de ce document devient le modèle des prochains documents du même type.
     */
    public function saveLayoutAsTemplate(): void
    {
        $setting = Setting::current();
        $layouts = $setting->document_layouts ?? [];
        $layouts[$this->documentType()] = DocumentLayout::templateFrom($this->documentType(), $this->layout);
        $setting->update(['document_layouts' => $layouts]);

        $this->layoutMessage = 'C’est maintenant la mise en page des prochains documents.';
        $this->documentChanged();
    }

    /**
     * Données d'affichage : adresse du logo, largeur à l'écran, société imprimée.
     */
    protected function layoutViewData(): array
    {
        $logo = $this->layout['logo'];

        return [
            'logoUrl' => match ($logo['mode']) {
                'hidden' => null,
                'custom' => route('document-logo', basename((string) $logo['path'])),
                default => route('logo'),
            },
            'logoWidthPx' => (int) round(DocumentLayout::logoWidthCm($this->layout) * 37.8),
            'company' => DocumentLayout::company($this->layout, Setting::current()->toSnapshot()),
        ];
    }
}
