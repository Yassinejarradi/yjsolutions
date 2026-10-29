<x-app-layout :title="$quote ? 'Devis brouillon' : 'Nouveau devis'">
    <x-slot name="header">
        <div>
            <a href="{{ route('quotes.index') }}" class="btn-link">← Tous les devis</a>
            <h1 class="mt-2 text-2xl font-bold">{{ $quote ? 'Devis brouillon' : 'Nouveau devis' }}</h1>
        </div>

        @if ($quote)
            <div class="flex flex-wrap gap-2">
                <form method="POST" action="{{ route('quotes.duplicate', $quote) }}">
                    @csrf
                    <button type="submit" class="btn-secondary">Dupliquer</button>
                </form>
                <form method="POST" action="{{ route('quotes.destroy', $quote) }}"
                      data-confirm-title="Supprimer ce brouillon ?" data-confirm="Il sera supprimé définitivement." data-confirm-label="Oui, supprimer" data-confirm-danger>
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-secondary text-accent">Supprimer le brouillon</button>
                </form>
            </div>
        @endif
    </x-slot>

    <livewire:quote-editor :quote="$quote" :preselected-client-id="$clientId ?? null" />
</x-app-layout>
