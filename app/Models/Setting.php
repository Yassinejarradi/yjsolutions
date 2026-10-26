<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Paramètres de la société. La table ne contient qu'une seule ligne : utiliser Setting::current().
 *
 * @property string $company_name
 * @property string|null $logo_path
 * @property int $default_vat_rate
 */
class Setting extends Model
{
    /** Disque où le logo téléversé est rangé (non public : servi par la route « logo »). */
    public const LOGO_DISK = 'local';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'default_vat_rate' => 'integer',
            'payment_terms_days' => 'integer',
            'quote_validity_days' => 'integer',
        ];
    }

    /**
     * La ligne unique des paramètres, créée vide si elle n'existe pas encore.
     */
    public static function current(): self
    {
        return static::query()->oldest('id')->firstOr(fn () => static::create());
    }

    /**
     * Chemin absolu du fichier logo : le logo téléversé, sinon public/logo.png.
     */
    public function logoFilePath(): string
    {
        if ($this->logo_path && Storage::disk(self::LOGO_DISK)->exists($this->logo_path)) {
            return Storage::disk(self::LOGO_DISK)->path($this->logo_path);
        }

        return public_path('logo.png');
    }

    /**
     * Ligne « Banque – Agence – RIB » imprimée sur les factures.
     */
    public function bankLine(): string
    {
        return collect([$this->bank_name, $this->bank_branch, $this->rib ? 'RIB : '.$this->rib : null])
            ->filter()
            ->implode(' – ');
    }
}
