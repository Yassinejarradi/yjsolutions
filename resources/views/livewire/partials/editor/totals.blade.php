{{-- Totaux et montant en lettres. Variables : $amountPaid (centimes, facture), $dueAmount (centimes). --}}
@php
    use App\Support\Money;
@endphp

    {{-- Totaux --}}
    <div class="ml-auto mt-4 w-full tabular-nums sm:w-[21rem]">
        <div class="flex items-center justify-between gap-2 px-2 py-1">
            <x-document.label key="subtotal" label="Libellé du sous-total" class="doc-input-fit" />
            <span>{{ Money::format($totals->subtotal) }}</span>
        </div>
        <div class="flex items-center justify-between gap-2 px-2 py-1">
            <label for="discount" class="flex items-center gap-1">
                <x-document.label key="discount" label="Libellé de la remise" class="doc-input-fit" />
                <input id="discount" wire:model.live.debounce.400ms="discountPercent" inputmode="decimal" placeholder="0"
                       @class(['doc-input w-16 text-right', 'is-invalid' => $errors->has('discountPercent')])> %
            </label>
            <span>{{ $totals->hasDiscount() ? '-'.Money::format($totals->discount) : '—' }}</span>
        </div>
        @error('discountPercent')<p class="px-2 text-sm font-semibold text-accent">{{ $message }}</p>@enderror
        <div class="flex items-center justify-between gap-2 px-2 py-1 font-semibold">
            <x-document.label key="total_ht" label="Libellé du total HT" class="doc-input-fit font-semibold" />
            <span>{{ Money::format($totals->totalHt) }}</span>
        </div>
        <div class="flex items-center justify-between gap-2 px-2 py-1">
            <label for="vat-rate" class="flex items-center gap-1">
                <x-document.label key="vat" label="Libellé de la TVA" class="doc-input-fit" />
                (<select id="vat-rate" wire:model.live="vatRate" class="doc-input inline w-auto py-0 pr-6">
                    @foreach (\App\Support\DocumentCalculator::VAT_RATES as $rate)
                        <option value="{{ $rate }}">{{ $rate }} %</option>
                    @endforeach
                </select>)
            </label>
            <span>{{ Money::format($totals->vat) }}</span>
        </div>
        @isset($layout['labels']['total_ttc'])
            <div class="flex items-center justify-between gap-2 px-2 py-1 font-bold">
                <x-document.label key="total_ttc" label="Libellé du total TTC" class="doc-input-fit font-bold" />
                <span>{{ Money::format($totals->totalTtc, true) }}</span>
            </div>
        @endisset
        @if (($amountPaid ?? 0) > 0 && ($layout['show']['payments'] ?? false))
            <div class="flex items-center justify-between gap-2 px-2 py-1">
                <x-document.label key="paid" label="Libellé des paiements reçus" class="doc-input-fit" />
                <span>-{{ Money::format($amountPaid, true) }}</span>
            </div>
        @endif
        <div class="mt-1 flex items-center justify-between gap-2 bg-brand px-2 py-1.5 font-bold text-white">
            <x-document.label key="due" label="Libellé du net à payer" class="doc-input-fit doc-input-head font-bold" />
            <span>{{ Money::format($dueAmount ?? $totals->totalTtc, true) }}</span>
        </div>
    </div>

    @if ($layout['show']['words'])
        <p class="mt-5 flex flex-wrap items-baseline gap-1">
            <x-document.label key="words" label="Libellé du montant en lettres" class="doc-input-fit font-bold" />
            <i class="text-muted">{{ $words }}</i>
        </p>
    @endif
