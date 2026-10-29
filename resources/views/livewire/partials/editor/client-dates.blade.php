{{-- Client (choix avec recherche) et dates. Variables : $secondDateField (validUntil, dueDate), $secondDateLabel. --}}
    {{-- Client et dates --}}
    <div class="mb-5 flex flex-col gap-5 sm:flex-row sm:justify-between">
        <div class="min-w-0 flex-1">
            <x-document.label key="client" label="Libellé du client" class="doc-input-fit doc-muted mb-1" />
            <div x-data="clientPicker()" @click.outside="open = false" data-no-autosave>

            <div class="relative">
                @if ($client)
                    <button type="button" @click="toggle()"
                            class="w-full rounded border border-dashed border-transparent px-1 py-0.5 text-left hover:border-line focus:border-brand focus:outline-none">
                        <span class="block text-[17px] font-bold">{{ $client->name }}</span>
                        @if ($client->fullAddress())<span class="block">{{ $client->fullAddress() }}</span>@endif
                        @if ($client->ice && $layout['show']['client_ice'])<span class="mt-1.5 block">ICE : {{ $client->ice }}</span>@endif
                        <span class="mt-1 block text-sm font-semibold text-brand">Changer de client</span>
                    </button>
                    @if (! $client->ice && $layout['show']['client_ice'])
                        {{-- Rappel à l'écran seulement : la feuille imprimée n'affiche rien tant que l'ICE manque. --}}
                        <form wire:submit="saveClientIce" data-no-autosave class="mt-2 rounded-lg border border-amber-300 bg-amber-50 p-3">
                            <label for="client-ice" class="block font-semibold text-amber-900">L’ICE de ce client n’est pas renseigné.</label>
                            <p class="text-sm text-amber-900">Il est obligatoire sur une facture. Tapez-le ici, il sera aussi enregistré sur la fiche du client.</p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <input id="client-ice" wire:model="clientIce" inputmode="numeric" maxlength="20" placeholder="15 chiffres"
                                       @class(['field-input w-48', 'is-invalid' => $errors->has('clientIce')])>
                                <button type="submit" class="btn-primary">Enregistrer l’ICE</button>
                            </div>
                            @error('clientIce')<p class="mt-1 text-sm font-semibold text-accent">{{ $message }}</p>@enderror
                        </form>
                    @endif
                @else
                    <div class="flex flex-wrap gap-2">
                        <button type="button" @click="toggle()" @class(['btn-secondary', 'ring-2 ring-accent' => $errors->has('clientId')])>Choisir le client…</button>
                        <button type="button" wire:click="openClientForm" class="btn-secondary">+ Nouveau client</button>
                    </div>
                @endif

                <div x-show="open" x-cloak x-transition.opacity
                     class="absolute left-0 z-30 mt-1 w-full min-w-[17rem] rounded-lg border border-line bg-white p-2 shadow-xl sm:w-96">
                    <label for="client-search" class="sr-only">Rechercher un client</label>
                    <input id="client-search" x-ref="search" x-model="query" type="search" autocomplete="off"
                           placeholder="Rechercher un client…" class="field-input"
                           @keydown.enter.prevent="firstVisible()?.click()" @keydown.escape="open = false">
                    <ul class="mt-2 max-h-72 overflow-y-auto">
                        @forelse ($clients as $option)
                            <li wire:key="client-option-{{ $option->id }}">
                                <button type="button" data-client-option
                                        x-show="matches(@js($option->name.' '.$option->city.' '.$option->ice))"
                                        wire:click="chooseClient({{ $option->id }})" @click="open = false; query = ''"
                                        @class(['block w-full rounded px-3 py-2 text-left hover:bg-brand-soft', 'bg-brand-soft' => $option->id === $clientId])>
                                    <span class="block font-semibold">{{ $option->name }}</span>
                                    <span class="block text-sm text-muted">{{ collect([$option->city, $option->ice ? 'ICE '.$option->ice : null])->filter()->implode(' · ') ?: ' ' }}</span>
                                </button>
                            </li>
                        @empty
                            <li class="px-3 py-2 text-muted">Aucun client pour l’instant.</li>
                        @endforelse
                    </ul>
                    <button type="button" wire:click="openClientForm" @click="open = false"
                            class="mt-2 w-full rounded border border-dashed border-brand bg-brand-soft px-3 py-2 font-bold text-brand">
                        + Nouveau client
                    </button>
                </div>
            </div>
            </div>
        </div>

        <div class="grid grid-cols-[auto_9.5rem] items-center gap-x-3 gap-y-1 sm:text-right">
            <x-document.label key="date" label="Libellé de la date" class="doc-input-fit sm:text-right" />
            <div>
                <input id="issue-date" aria-label="Date" wire:model.live.blur="issueDate" placeholder="JJ/MM/AAAA" inputmode="numeric"
                       @class(['doc-input text-right text-muted', 'is-invalid' => $errors->has('issueDate')])>
                @error('issueDate')<p class="text-right text-xs font-semibold text-accent">{{ $message }}</p>@enderror
            </div>
            <x-document.label key="due_date" label="Libellé de la deuxième date" class="doc-input-fit sm:text-right" />
            <div>
                <input id="second-date" aria-label="{{ $secondDateLabel }}" wire:model.live.blur="{{ $secondDateField }}" placeholder="JJ/MM/AAAA" inputmode="numeric"
                       @class(['doc-input text-right text-muted', 'is-invalid' => $errors->has($secondDateField)])>
                @error($secondDateField)<p class="text-right text-xs font-semibold text-accent">{{ $message }}</p>@enderror
            </div>
            @if ($layout['show']['reference'])
                <x-document.label key="reference" label="Libellé de la référence" class="doc-input-fit sm:text-right" />
                <input id="reference" aria-label="Référence" wire:model.live.debounce.800ms="reference" placeholder="facultatif" class="doc-input text-right text-muted">
            @endif
        </div>
    </div>
