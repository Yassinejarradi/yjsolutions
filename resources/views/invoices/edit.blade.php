<x-app-layout :title="$invoice ? 'Facture brouillon' : 'Nouvelle facture'">
    <x-slot name="header">
        <div>
            <a href="{{ route('invoices.index') }}" class="btn-link">← Toutes les factures</a>
            <h1 class="mt-2 text-3xl font-bold">{{ $invoice ? 'Facture brouillon' : 'Nouvelle facture' }}</h1>
            @if ($invoice?->quote)
                <p class="mt-1 text-lg text-muted">
                    Créée à partir du devis <a href="{{ route('quotes.show', $invoice->quote) }}" class="btn-link">{{ $invoice->quote->number }}</a>
                </p>
            @endif
        </div>

        @if ($invoice)
            <div class="flex flex-wrap gap-2">
                <form method="POST" action="{{ route('invoices.duplicate', $invoice) }}">
                    @csrf
                    <button type="submit" class="btn-secondary">Dupliquer</button>
                </form>
                <form method="POST" action="{{ route('invoices.destroy', $invoice) }}"
                      onsubmit="return confirm('Supprimer définitivement ce brouillon ?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-secondary text-accent">Supprimer le brouillon</button>
                </form>
            </div>
        @endif
    </x-slot>

    <livewire:invoice-editor :invoice="$invoice" :preselected-client-id="$clientId ?? null" />
</x-app-layout>
