@php
    use App\Support\Money;
@endphp

<div>
    <div class="card mb-6 space-y-5">
        <div>
            <label for="quote-search" class="field-label">Rechercher</label>
            <input id="quote-search" type="search" wire:model.live.debounce.300ms="search" class="field-input"
                   placeholder="N° de devis, client, référence…" autocomplete="off">
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label for="quote-status" class="field-label">Statut</label>
                <select id="quote-status" wire:model.live="status" class="field-input">
                    <option value="">Tous les statuts</option>
                    @foreach ($statuses as $option)
                        <option value="{{ $option->value }}">{{ $option->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="quote-client" class="field-label">Client</label>
                <select id="quote-client" wire:model.live="clientId" class="field-input">
                    <option value="">Tous les clients</option>
                    @foreach ($clients as $client)
                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="quote-period" class="field-label">Période (date du devis)</label>
                <select id="quote-period" wire:model.live="period" class="field-input">
                    @foreach ($periods as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        @if ($period === 'dates')
            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label for="quote-from" class="field-label">Du</label>
                    <input id="quote-from" wire:model.live.blur="from" class="field-input" placeholder="JJ/MM/AAAA" inputmode="numeric">
                </div>
                <div>
                    <label for="quote-to" class="field-label">Au</label>
                    <input id="quote-to" wire:model.live.blur="to" class="field-input" placeholder="JJ/MM/AAAA" inputmode="numeric">
                </div>
            </div>
        @endif

        <div class="flex flex-wrap items-center justify-between gap-3 text-lg">
            <p class="text-muted">
                {{ $count }} devis{{ $count ? ' · total '.Money::format($total, true).' TTC' : '' }}
            </p>
            @if ($filtered)
                <button type="button" wire:click="resetFilters" class="btn-link">Effacer les filtres</button>
            @endif
        </div>
    </div>

    <div class="card p-0 sm:p-0" wire:loading.class="opacity-60">
        @if ($quotes->isEmpty())
            <p class="px-5 py-10 text-center text-lg text-muted sm:px-7">
                @if ($filtered)
                    Aucun devis ne correspond à ces critères.
                @else
                    Aucun devis pour l’instant. Cliquez sur « + Nouveau devis » pour commencer.
                @endif
            </p>
        @else
            {{-- En-têtes (ordinateur et tablette) --}}
            <div class="hidden grid-cols-[9rem_minmax(0,1fr)_7rem_8.5rem_8rem] gap-4 border-b border-line px-7 py-3 text-sm font-semibold uppercase tracking-wide text-muted md:grid">
                <span>Numéro</span><span>Client</span><span>Date</span><span class="text-right">Total TTC</span><span>Statut</span>
            </div>
            <ul class="divide-y divide-line">
                @foreach ($quotes as $quote)
                    @php($status = $quote->displayStatus())
                    <li wire:key="quote-{{ $quote->id }}">
                        <a href="{{ route('quotes.show', $quote) }}"
                           class="grid grid-cols-2 gap-x-4 gap-y-1 px-5 py-4 hover:bg-brand-soft md:grid-cols-[9rem_minmax(0,1fr)_7rem_8.5rem_8rem] md:items-center md:px-7">
                            <span class="font-semibold {{ $quote->number ? 'text-accent' : 'italic text-muted' }}">{{ $quote->number ?? 'Brouillon' }}</span>
                            <span class="order-3 col-span-2 truncate text-lg font-semibold md:order-none md:col-span-1">
                                {{ $quote->clientDetails()['name'] ?: 'Client à choisir' }}
                            </span>
                            <span class="order-4 text-muted md:order-none">{{ $quote->issue_date->format('d/m/Y') }}</span>
                            <span class="order-5 text-right font-semibold tabular-nums md:order-none">{{ Money::format($quote->total_ttc, true) }}</span>
                            <span class="order-2 text-right md:order-none md:text-left">
                                <span class="rounded px-2 py-0.5 text-sm font-semibold {{ $status->badgeClasses() }}">{{ $status->label() }}</span>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    @if ($quotes->hasPages())
        <div class="mt-6">{{ $quotes->links() }}</div>
    @endif
</div>
