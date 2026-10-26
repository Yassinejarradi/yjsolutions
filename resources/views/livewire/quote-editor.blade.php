@php
    // Éditeur de devis : morceaux communs dans livewire/partials/editor (partagés avec les factures).
    $shared = ['documentType' => 'quote', 'documentLabel' => 'devis'];
@endphp

<div x-data="documentAutosave()"
     @focus-line.window="setTimeout(() => document.querySelector(`[data-line='${$event.detail.key}'] [data-field=description]`)?.focus(), 60)">

    @include('livewire.partials.editor.toolbar', [
        'documentId' => $quoteId,
        'routePrefix' => 'quotes',
        'newDocumentHint' => 'Nouveau devis : il sera enregistré dès la première saisie.',
        'sendLabel' => 'Marquer comme envoyé',
        'sendConfirm' => "Marquer ce devis comme envoyé ?\n\nIl recevra son numéro définitif et ne pourra plus être modifié (vous pourrez toujours le dupliquer).",
    ])

    {{-- ===================== Feuille A4 ===================== --}}
    <div class="doc-sheet">
        @include('livewire.partials.editor.header', ['headerAmount' => $totals->totalTtc])
        @include('livewire.partials.editor.client-dates', ['secondDateField' => 'validUntil', 'secondDateLabel' => 'Date de validité'])
        @include('livewire.partials.editor.lines')
        @include('livewire.partials.editor.totals')

        {{-- Conditions --}}
        <div class="mt-3 grid gap-1">
            @if ($layout['show']['validity'])
                <p class="flex flex-wrap items-baseline gap-1">
                    <x-document.label key="validity" label="Libellé de la validité" class="doc-input-fit font-bold" />
                    <span>{{ $validityDays !== null ? $validityDays.' jours, jusqu’au '.$validUntil : '—' }}</span>
                </p>
            @endif
            <div @class(['flex flex-col sm:flex-row sm:items-baseline sm:gap-1.5', 'hidden' => ! $layout['show']['payment_terms']])>
                <x-document.label key="payment_terms" label="Libellé des modalités de paiement" class="doc-input-fit shrink-0 font-bold" />
                <input id="payment-terms" aria-label="Modalités de paiement" wire:model.live.debounce.800ms="paymentTerms" placeholder="ex. 50 % à la commande, solde à la livraison" class="doc-input">
            </div>
            <div @class(['flex flex-col sm:flex-row sm:items-baseline sm:gap-1.5', 'hidden' => ! $layout['show']['completion_delay']])>
                <x-document.label key="completion_delay" label="Libellé du délai de réalisation" class="doc-input-fit shrink-0 font-bold" />
                <input id="completion-delay" aria-label="Délai de réalisation" wire:model.live.debounce.800ms="completionDelay" placeholder="ex. 3 semaines" class="doc-input">
            </div>
            @include('livewire.partials.editor.notes')
        </div>

        @include('livewire.partials.editor.signatures-footer')
    </div>

    @include('livewire.partials.editor.overlays', $shared)
</div>
