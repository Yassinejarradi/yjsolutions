<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Compteur de numérotation (sans trou) par type de document et par année.
 */
class Sequence extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['year' => 'integer', 'last_number' => 'integer'];
    }

    /**
     * Réserve le numéro suivant pour ce type et cette année.
     *
     * À appeler dans la transaction qui enregistre le document : si elle échoue,
     * l'incrément est annulé avec elle et aucun numéro n'est perdu.
     */
    public static function next(string $type, int $year): int
    {
        return DB::transaction(function () use ($type, $year) {
            // Crée le compteur de l'année s'il n'existe pas (sans erreur si un autre processus l'a créé).
            static::query()->insertOrIgnore([
                'type' => $type,
                'year' => $year,
                'last_number' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $sequence = static::query()
                ->where('type', $type)
                ->where('year', $year)
                ->lockForUpdate()
                ->firstOrFail();

            $sequence->increment('last_number');

            return $sequence->last_number;
        });
    }
}
