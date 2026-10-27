@php
    use App\Support\Money;
@endphp

<div>
    <div class="card mb-6 space-y-5">
        <div>
            <label for="invoice-search" class="field-label">Rechercher</label>
            <input id="invoice-search" type="search" wire:model.live.debounce.300ms="search" class="field-input"
                   placeholder="N° de facture, client, bon de commande…" autocomplete="off">
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label for="invoice-status" class="field-label">Statut</label>
                <select id="invoice-status" wire:model.live="status" class="field-input">
                    <option value="">Tous les statuts</option>
                    @foreach ($statuses as $option)
                        <option value="{{ $option->value }}">{{ $option->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="invoice-client" class="field-label">Client</label>
                <select id="invoice-client" wire:model.live="clientId" class="field-input">
                    <option value="">Tous les clients</option>
                    @foreach ($clients as $client)
                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="invoice-period" class="field-label">Période (date de facture)</label>
                <select id="invoice-period" wire:model.live="period" class="field-input">
                    @foreach ($periods as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        @if ($period === 'dates')
            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label for="invoice-from" class="field-label">Du</label>
                    <input id="invoice-from" wire:model.live.blur="from" class="field-input" placeholder="JJ/MM/AAAA" inputmode="numeric">
                </div>
                <div>
                    <label for="invoice-to" class="field-label">Au</label>
                    <input id="invoice-to" wire:model.live.blur="to" class="field-input" placeholder="JJ/MM/AAAA" inputmode="numeric">
                </div>
            </div>
        @endif

        <div class="flex flex-wrap items-center justify-between gap-3 text-lg">
            <p class="text-muted">
                {{ $count }} {{ $count > 1 ? 'factures' : 'facture' }}
                @if ($total)
                    · facturé {{ Money::format($total, true) }} TTC
                    · <span @class(['font-semibold text-accent' => $outstanding > 0])>reste à payer {{ Money::format($outstanding, true) }}</span>
                @endif
            </p>
            @if ($filtered)
                <button type="button" wire:click="resetFilters" class="btn-link">Effacer les filtres</button>
            @endif
        </div>
    </div>

    <div class="card overflow-hidden p-0 sm:p-0" wire:loading.class="opacity-60">
        @if ($invoices->isEmpty())
            <p class="px-5 py-10 text-center text-lg text-muted sm:px-7">
                @if ($filtered)
                    Aucune facture ne correspond à ces critères.
                @else
                    Aucune facture pour l’instant. Cliquez sur « + Nouvelle facture », ou convertissez un devis.
                @endif
            </p>
        @else
            {{-- En-têtes (ordinateur et tablette) --}}
            <div class="hidden grid-cols-[7.5rem_minmax(0,1fr)_6rem_9rem_9rem_8.5rem] gap-3 rounded-t-2xl border-b border-line bg-slate-50/80 px-7 py-3 text-sm font-semibold uppercase tracking-wide text-muted md:grid">
                <span>Numéro</span><span>Client</span><span>Échéance</span><span class="text-right">Total TTC</span><span class="text-right">Reste à payer</span><span>Statut</span>
            </div>
            <ul class="divide-y divide-line">
                @foreach ($invoices as $invoice)
                    @php($status = $invoice->displayStatus())
                    <li wire:key="invoice-{{ $invoice->id }}">
                        <a href="{{ route('invoices.show', $invoice) }}"
                           class="grid grid-cols-2 gap-x-3 gap-y-1 px-5 py-4 list-row md:grid-cols-[7.5rem_minmax(0,1fr)_6rem_9rem_9rem_8.5rem] md:items-center md:px-7">
                            <span class="font-semibold {{ $invoice->number ? 'text-accent' : 'italic text-muted' }}">{{ $invoice->number ?? 'Brouillon' }}</span>
                            <span class="order-3 col-span-2 truncate text-lg font-semibold md:order-none md:col-span-1">
                                {{ $invoice->clientDetails()['name'] ?: 'Client à choisir' }}
                            </span>
                            <span class="order-4 text-muted md:order-none">
                                <span class="md:hidden">Échéance </span>{{ $invoice->due_date->format('d/m/Y') }}
                            </span>
                            <span class="order-5 whitespace-nowrap text-right font-semibold tabular-nums md:order-none">{{ Money::format($invoice->total_ttc, true) }}</span>
                            <span class="order-6 col-span-2 whitespace-nowrap text-right tabular-nums md:order-none md:col-span-1">
                                @if ($invoice->isOpen() && $invoice->balance() > 0)
                                    <span class="md:hidden text-muted">Reste à payer </span><span class="font-semibold text-accent">{{ Money::format($invoice->balance(), true) }}</span>
                                @else
                                    <span class="hidden text-muted md:inline">—</span>
                                @endif
                            </span>
                            <span class="order-2 text-right md:order-none md:text-left">
                                <x-badge :status="$status" />
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    @if ($invoices->hasPages())
        <div class="mt-6">{{ $invoices->links() }}</div>
    @endif
</div>
