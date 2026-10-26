<?php

namespace App\Livewire\Concerns;

use App\Models\DocumentLine;
use App\Support\DecimalInput;
use App\Support\DocumentCalculator;
use App\Support\DocumentTotals;
use App\Support\Money;
use App\Support\Quantity;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Renderless;

/**
 * Lignes d'un document dans l'éditeur (devis, et facture en phase 4).
 *
 * Les saisies restent des chaînes telles que tapées ("1 250,5") ; les montants affichés
 * sont toujours calculés côté serveur par DocumentCalculator, comme pour le PDF.
 */
trait EditsDocumentLines
{
    /** @var list<array{key: string, description: string, quantity: string, unit_price: string}> */
    public array $lines = [];

    public string $vatRate = '20';

    public string $discountPercent = '';

    /** Dernière ligne supprimée, pour « Annuler » : ['line' => [...], 'index' => int]. */
    public ?array $removedLine = null;

    public function addLine(): void
    {
        $line = self::blankLine();
        $this->lines[] = $line;

        $this->dispatch('focus-line', key: $line['key']);
        $this->documentChanged();
    }

    public function removeLine(string $key): void
    {
        $index = $this->lineIndex($key);

        if ($index === null) {
            return;
        }

        $this->removedLine = ['line' => $this->lines[$index], 'index' => $index];
        array_splice($this->lines, $index, 1);

        if ($this->lines === []) {
            $this->lines[] = self::blankLine();
        }

        $this->documentChanged();
    }

    public function undoRemove(): void
    {
        if (! $this->removedLine) {
            return;
        }

        // Si la seule ligne restante est la ligne vide ajoutée à la suppression, on la remplace.
        if (count($this->lines) === 1 && self::isBlankInput($this->lines[0])) {
            $this->lines = [];
        }

        $index = min($this->removedLine['index'], count($this->lines));
        array_splice($this->lines, $index, 0, [$this->removedLine['line']]);
        $this->removedLine = null;

        $this->documentChanged();
    }

    public function duplicateLine(string $key): void
    {
        $index = $this->lineIndex($key);

        if ($index === null) {
            return;
        }

        $copy = ['key' => self::newKey()] + $this->lines[$index];
        array_splice($this->lines, $index + 1, 0, [$copy]);

        $this->documentChanged();
    }

    /** Flèches ↑ (-1) et ↓ (+1). */
    public function moveLine(string $key, int $direction): void
    {
        $index = $this->lineIndex($key);

        if ($index !== null) {
            $this->sortLine($key, $index + ($direction < 0 ? -1 : 1));
        }
    }

    /** Glisser-déposer (wire:sort) : place la ligne à la position donnée (0 = en haut). */
    public function sortLine(string $key, int $position): void
    {
        $index = $this->lineIndex($key);
        $position = max(0, min($position, count($this->lines) - 1));

        if ($index === null || $index === $position) {
            return;
        }

        $line = array_splice($this->lines, $index, 1)[0];
        array_splice($this->lines, $position, 0, [$line]);

        $this->documentChanged();
    }

    /**
     * Auto-complétion : lignes déjà saisies dans les autres documents dont la désignation contient le texte.
     * La plus récente l'emporte pour une même désignation (son prix est proposé).
     *
     * @return list<array{description: string, unit_price: string, price_label: string}>
     */
    #[Renderless]
    public function suggest(string $term): array
    {
        $term = trim($term);

        if (mb_strlen($term) < 2) {
            return [];
        }

        return DocumentLine::query()
            ->where('description', 'like', '%'.$term.'%')
            ->when($this->currentDocument(), fn ($q, $document) => $q->whereNot(fn ($q) => $q
                ->where('document_type', $document->getMorphClass())
                ->where('document_id', $document->getKey())))
            ->latest('id')
            ->limit(200)
            ->get(['description', 'unit_price'])
            ->unique(fn (DocumentLine $line) => mb_strtolower(trim($line->description)))
            ->take(8)
            ->map(fn (DocumentLine $line) => [
                'description' => $line->description,
                'unit_price' => $line->unit_price === null ? '' : Money::format($line->unit_price),
                'price_label' => $line->unit_price === null ? '' : Money::format($line->unit_price, true),
            ])
            ->values()
            ->all();
    }

    /**
     * Totaux de la saisie en cours (mêmes règles que le PDF).
     */
    public function currentTotals(): DocumentTotals
    {
        return DocumentCalculator::calculate($this->parsedLines(), (int) $this->vatRate, $this->parsedDiscount());
    }

    /**
     * Lignes prêtes à enregistrer : quantité décimale, prix en centimes (null si vide ou invalide).
     *
     * @return Collection<int, array{description: string, quantity: string, unit_price: int|null}>
     */
    protected function parsedLines(): Collection
    {
        return collect($this->lines)->map(fn (array $line) => [
            'description' => trim($line['description']),
            'quantity' => DocumentCalculator::normalizeQuantity($line['quantity']),
            'unit_price' => Money::parse($line['unit_price']),
        ]);
    }

    protected function parsedDiscount(): string
    {
        return (string) (DecimalInput::parse($this->discountPercent, 2) ?? '0');
    }

    /**
     * Règles de validation des lignes, de la TVA et de la remise.
     */
    protected function lineRules(): array
    {
        return [
            'lines' => ['array', 'max:200'],
            'lines.*.description' => ['nullable', 'string', 'max:2000'],
            'lines.*.quantity' => ['nullable', self::decimalRule(3, '-999999999', '999999999')],
            'lines.*.unit_price' => ['nullable', self::decimalRule(2, '-9999999999', '9999999999')],
            'vatRate' => ['required', 'in:'.implode(',', DocumentCalculator::VAT_RATES)],
            'discountPercent' => ['nullable', self::decimalRule(2, '0', '100')],
        ];
    }

    protected function lineMessages(): array
    {
        return [
            'lines.*.quantity' => 'Quantité invalide.',
            'lines.*.unit_price' => 'Prix invalide.',
            'discountPercent' => 'La remise doit être un pourcentage entre 0 et 100.',
        ];
    }

    /**
     * Nombre saisi au format français (« 1 250,50 »), compris entre $min et $max.
     */
    protected static function decimalRule(int $scale, string $min, string $max): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($scale, $min, $max) {
            if ($value === null || trim((string) $value) === '') {
                return;
            }

            $decimal = DecimalInput::parse((string) $value, $scale);

            if ($decimal === null || $decimal->isLessThan($min) || $decimal->isGreaterThan($max)) {
                $fail('invalid');
            }
        };
    }

    /**
     * @param  iterable<DocumentLine>  $lines
     */
    protected function fillLinesFrom(iterable $lines): void
    {
        $this->lines = collect($lines)->map(fn (DocumentLine $line) => [
            'key' => self::newKey(),
            'description' => $line->description,
            'quantity' => Quantity::format($line->quantity),
            'unit_price' => $line->unit_price === null ? '' : Money::format($line->unit_price),
        ])->values()->all();

        if ($this->lines === []) {
            $this->lines[] = self::blankLine();
        }
    }

    /**
     * Prévient le navigateur qu'il y a quelque chose à enregistrer (sauvegarde automatique).
     */
    protected function documentChanged(): void
    {
        $this->dispatch('document-changed');
    }

    /** Document en cours d'édition (null s'il n'est pas encore enregistré). */
    abstract protected function currentDocument(): ?object;

    protected function lineIndex(string $key): ?int
    {
        $index = array_search($key, array_column($this->lines, 'key'), true);

        return $index === false ? null : $index;
    }

    protected static function blankLine(): array
    {
        return ['key' => self::newKey(), 'description' => '', 'quantity' => '1', 'unit_price' => ''];
    }

    protected static function isBlankInput(array $line): bool
    {
        return trim($line['description']) === '' && trim($line['unit_price']) === '';
    }

    protected static function newKey(): string
    {
        return Str::lower(Str::random(10));
    }
}
