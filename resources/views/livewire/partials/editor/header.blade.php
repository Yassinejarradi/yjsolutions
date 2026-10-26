{{-- En-tête de la feuille : logo, titre, numéro, montant. Variable : $headerAmount (centimes). --}}
@php
    use App\Support\Money;
@endphp

    {{-- En-tête --}}
    <div class="flex items-start justify-between gap-4">
        <div class="shrink-0">
            @if ($logoUrl)
                <button type="button" @click="layoutPanel = true" title="Modifier ou retirer le logo"
                        class="rounded border border-dashed border-transparent p-0.5 hover:border-line focus:border-brand focus:outline-none">
                    <img src="{{ $logoUrl }}" alt="Logo" class="max-w-[28vw] sm:max-w-none" style="width: {{ $logoWidthPx }}px">
                </button>
            @else
                <button type="button" @click="layoutPanel = true"
                        class="rounded border border-dashed border-line px-3 py-6 text-sm font-semibold text-muted hover:border-brand hover:text-brand">
                    + Ajouter un logo
                </button>
            @endif
        </div>
        <div class="min-w-0 flex-1 text-right">
            <x-document.label key="title" label="Titre du document" class="doc-title" />
            <p class="mt-1 flex flex-wrap items-baseline justify-end gap-1 font-bold">
                <x-document.label key="number" label="Libellé du numéro" class="doc-input-fit text-right font-bold" />
                <span class="font-normal italic text-muted">{{ $numberHint ?? 'attribué à l’envoi' }}</span>
            </p>
            @if ($layout['show']['amount'])
                <x-document.label key="amount" label="Libellé du montant" class="doc-muted mt-3 text-right text-[13px]" />
                <p class="text-[22px] font-bold tabular-nums">{{ Money::format($headerAmount, true) }}</p>
            @endif
        </div>
    </div>
    <hr class="doc-rule">
