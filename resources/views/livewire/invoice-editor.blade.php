@php
    // Éditeur de facture : mêmes morceaux que l'éditeur de devis (livewire/partials/editor).
    $shared = ['documentType' => 'invoice', 'documentLabel' => 'factures'];
@endphp

<div x-data="documentAutosave()"
     @focus-line.window="setTimeout(() => document.querySelector(`[data-line='${$event.detail.key}'] [data-field=description]`)?.focus(), 60)">

    @include('livewire.partials.editor.toolbar', [
        'documentId' => $invoiceId,
        'routePrefix' => 'invoices',
        'newDocumentHint' => 'Nouvelle facture : elle sera enregistrée dès la première saisie.',
        'sendLabel' => 'Émettre la facture',
        'sendAction' => 'issue',
        'sendTitle' => 'Émettre cette facture ?',
        'sendConfirm' => 'Elle recevra son numéro définitif et ne pourra plus être modifiée ni supprimée (seulement annulée).',
        'sendConfirmLabel' => 'Oui, émettre la facture',
    ])

    @if ($tooEarly)
        <div role="alert" class="mb-6 rounded-lg border-l-4 border-amber-500 bg-amber-50 px-5 py-4 text-lg text-amber-900">
            La date de facture est antérieure à la dernière facture émise ({{ $lastIssueDate }}).
            Elle ne pourra pas être émise à cette date : les numéros doivent suivre l’ordre des dates.
        </div>
    @endif

    {{-- ===================== Feuille A4 ===================== --}}
    <div class="doc-sheet">
        @include('livewire.partials.editor.header', ['headerAmount' => $totals->totalTtc, 'numberHint' => 'attribué à l’émission'])
        @include('livewire.partials.editor.client-dates', ['secondDateField' => 'dueDate', 'secondDateLabel' => 'Date d’échéance'])
        @include('livewire.partials.editor.lines')
        @include('livewire.partials.editor.totals')

        {{-- Coordonnées bancaires et mentions --}}
        <div class="mt-3 grid gap-1">
            @if ($layout['show']['bank'])
                <p class="flex flex-wrap items-baseline gap-1">
                    <x-document.label key="bank" label="Libellé des coordonnées bancaires" class="doc-input-fit font-bold" />
                    @if ($company['bank_line'] ?? '')
                        <span>{{ $company['bank_line'] }}</span>
                    @else
                        <a href="{{ route('settings.company') }}" class="text-sm font-semibold text-accent underline">
                            Banque, agence et RIB à saisir dans Paramètres › Société
                        </a>
                    @endif
                </p>
            @endif
            @include('livewire.partials.editor.notes')
        </div>

        @include('livewire.partials.editor.signatures-footer')
    </div>

    @include('livewire.partials.editor.overlays', $shared)
</div>
