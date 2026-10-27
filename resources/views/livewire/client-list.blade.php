<div>
    <div class="card mb-6 space-y-5">
        <div>
            <label for="client-search" class="field-label">Rechercher un client</label>
            <input id="client-search" type="search" wire:model.live.debounce.300ms="search" class="field-input"
                   placeholder="Nom, ICE, ville, contact…" autocomplete="off">
        </div>

        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="inline-flex overflow-hidden rounded-lg border border-line" role="group" aria-label="Afficher">
                @foreach (['actifs' => 'Clients actifs', 'archives' => 'Clients archivés'] as $value => $label)
                    <button type="button" wire:click="$set('status', '{{ $value }}')"
                            @class([
                                'px-5 py-2.5 font-semibold transition',
                                'bg-brand text-white' => $status === $value,
                                'bg-white text-ink hover:bg-brand-soft' => $status !== $value,
                            ])
                            aria-pressed="{{ $status === $value ? 'true' : 'false' }}">
                        {{ $label }}
                        @if ($value === 'archives' && $archivedCount)
                            ({{ $archivedCount }})
                        @endif
                    </button>
                @endforeach
            </div>

            <button type="button" wire:click="toggleSort" class="btn-secondary">
                Tri par nom : {{ $sort === 'desc' ? 'Z → A' : 'A → Z' }}
            </button>
        </div>
    </div>

    <div class="card overflow-hidden p-0 sm:p-0" wire:loading.class="opacity-60">
        @if ($clients->isEmpty())
            <p class="px-5 py-10 text-center text-lg text-muted sm:px-7">
                @if (trim($search) !== '')
                    Aucun client ne correspond à « {{ $search }} ».
                @elseif ($archived)
                    Aucun client archivé.
                @else
                    Aucun client pour l’instant. Cliquez sur « + Nouveau client » pour en ajouter un.
                @endif
            </p>
        @else
            <ul class="divide-y divide-line">
                @foreach ($clients as $client)
                    <li wire:key="client-{{ $client->id }}">
                        <a href="{{ route('clients.show', $client) }}"
                           class="list-row flex flex-col gap-1 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:gap-6 sm:px-7">
                            <div class="flex min-w-0 items-center gap-4">
                                <x-avatar :name="$client->name" />
                                <div class="min-w-0">
                                    <p class="text-lg font-semibold text-ink">{{ $client->name }}</p>
                                    <p class="text-muted">
                                        {{ collect([$client->city, $client->ice ? 'ICE '.$client->ice : null])->filter()->implode(' · ') ?: '—' }}
                                    </p>
                                </div>
                            </div>
                            <div class="pl-[3.75rem] text-muted sm:pl-0 sm:text-right">
                                <p>{{ collect([$client->contact_name, $client->phone])->filter()->implode(' · ') }}</p>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    @if ($clients->hasPages())
        <div class="mt-6">{{ $clients->links() }}</div>
    @endif
</div>
