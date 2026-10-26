<?php

namespace App\Models\Concerns;

use App\Models\DocumentLine;
use App\Support\DocumentCalculator;
use App\Support\DocumentTotals;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;

/**
 * Lignes et totaux d'un document (devis, et facture en phase 4).
 *
 * Le modèle doit avoir les colonnes vat_rate, discount_percent, subtotal, discount,
 * total_ht, vat et total_ttc. Les totaux sont toujours ceux de DocumentCalculator.
 */
trait HasDocumentLines
{
    public function lines(): MorphMany
    {
        return $this->morphMany(DocumentLine::class, 'document')->orderBy('position');
    }

    /**
     * Remplace toutes les lignes, recalcule les montants et les totaux, puis enregistre le document.
     *
     * @param  iterable<array{description: string, quantity: string|null, unit_price: int|null}>  $lines
     */
    public function replaceLines(iterable $lines): void
    {
        $lines = collect($lines)->values();
        $totals = DocumentCalculator::calculate($lines, (int) $this->vat_rate, (string) $this->discount_percent);

        DB::transaction(function () use ($lines, $totals) {
            $this->fillTotals($totals)->save();

            $this->lines()->delete();
            $this->lines()->createMany($lines->map(fn (array $line, int $i) => [
                'position' => $i + 1,
                'description' => $line['description'],
                'quantity' => DocumentCalculator::normalizeQuantity($line['quantity']),
                'unit_price' => $line['unit_price'],
                'amount' => $totals->lineAmounts[$i],
            ])->all());
        });

        $this->unsetRelation('lines');
    }

    /**
     * Totaux recalculés à partir des lignes enregistrées.
     */
    public function calculateTotals(?Collection $lines = null): DocumentTotals
    {
        $lines ??= $this->lines;

        return DocumentCalculator::calculate(
            $lines->map(fn (DocumentLine $line) => ['quantity' => $line->quantity, 'unit_price' => $line->unit_price]),
            (int) $this->vat_rate,
            (string) $this->discount_percent,
        );
    }

    /**
     * Lignes imprimées : sans les lignes entièrement vides.
     */
    public function printableLines(): Collection
    {
        return $this->lines->reject(fn (DocumentLine $line) => $line->isBlank())->values();
    }

    protected function fillTotals(DocumentTotals $totals): static
    {
        return $this->forceFill([
            'subtotal' => $totals->subtotal,
            'discount' => $totals->discount,
            'total_ht' => $totals->totalHt,
            'vat' => $totals->vat,
            'total_ttc' => $totals->totalTtc,
        ]);
    }
}
