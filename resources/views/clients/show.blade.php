@php
    use App\Support\Money;

    // Coordonnées affichées seulement si elles sont remplies.
    $details = collect([
        'Adresse' => $client->fullAddress(),
        'ICE' => $client->ice,
        'IF' => $client->if_number,
        'RC' => $client->rc,
        'Contact principal' => $client->contact_name,
        'Téléphone' => $client->phone,
        'Email' => $client->email,
    ])->filter();
@endphp

<x-app-layout :title="$client->name">
    <x-slot name="header">
        <div class="min-w-0">
            <a href="{{ route('clients.index') }}" class="btn-link">← Tous les clients</a>
            <h1 class="mt-3 flex flex-wrap items-center gap-4 text-2xl font-extrabold">
                <x-avatar :name="$client->name" size="lg" />
                {{ $client->name }}
                @if ($client->isArchived())
                    <span class="ml-2 align-middle rounded bg-desk px-2 py-1 text-base font-semibold text-muted">Archivé</span>
                @endif
            </h1>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('clients.statement', $client) }}" target="_blank" class="btn-secondary">Relevé de compte (PDF)</a>
            <a href="{{ route('clients.edit', $client) }}" class="btn-primary">Modifier le client</a>
        </div>
    </x-slot>

    @if ($client->isArchived())
        <div class="mb-6 rounded-lg border-l-4 border-muted bg-white px-5 py-4 text-lg">
            Ce client est archivé depuis le {{ $client->archived_at->format('d/m/Y') }} : il n’est plus proposé pour les nouveaux devis et factures.
            Ses anciens documents restent consultables.
        </div>
    @endif

    {{-- Totaux des factures émises (hors brouillons et factures annulées). --}}
    @php($outstanding = $client->outstandingBalance())
    <div class="mb-8 grid gap-5 sm:grid-cols-2">
        <div class="card">
            <p class="text-lg text-muted">Total facturé</p>
            <p class="mt-1 text-2xl font-bold tabular-nums">{{ Money::format($client->billedTotal(), true) }}</p>
        </div>
        <div class="card">
            <p class="text-lg text-muted">Reste à payer</p>
            <p @class(['mt-1 text-2xl font-bold tabular-nums', 'text-accent' => $outstanding > 0])>{{ Money::format($outstanding, true) }}</p>
        </div>
    </div>

    <div class="grid gap-8 lg:grid-cols-3">
        <section class="card lg:col-span-1">
            <h2 class="card-title">Coordonnées</h2>

            @if ($details->isEmpty())
                <p class="text-muted">Aucune coordonnée enregistrée.</p>
            @else
                <dl class="space-y-3">
                    @foreach ($details as $label => $value)
                        <div>
                            <dt class="text-sm text-muted">{{ $label }}</dt>
                            <dd class="break-words font-semibold">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            @endif

            <div class="mt-5 border-t border-line pt-5">
                <p class="text-sm text-muted">Délai de paiement</p>
                <p class="font-semibold">
                    {{ $client->effectivePaymentTermsDays() }} jours
                    @if ($client->payment_terms_days === null)
                        <span class="font-normal text-muted">(délai des Paramètres)</span>
                    @endif
                </p>
            </div>

            @if ($client->notes)
                <div class="mt-5 border-t border-line pt-5">
                    <p class="text-sm text-muted">Notes</p>
                    <p class="whitespace-pre-line">{{ $client->notes }}</p>
                </div>
            @endif
        </section>

        <div class="space-y-8 lg:col-span-2">
            <section class="card">
                <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-xl font-bold">Devis</h2>
                    @unless ($client->isArchived())
                        <a href="{{ route('quotes.create', ['client' => $client->id]) }}" class="btn-secondary">+ Nouveau devis</a>
                    @endunless
                </div>

                @if ($quotes->isEmpty())
                    <p class="text-muted">Aucun devis pour ce client.</p>
                @else
                    <ul class="-mx-5 divide-y divide-line border-t border-line sm:-mx-7">
                        @foreach ($quotes as $quote)
                            @php($status = $quote->displayStatus())
                            <li>
                                <a href="{{ route('quotes.show', $quote) }}"
                                   class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 px-5 py-3 list-row sm:px-7">
                                    <span>
                                        <span class="font-semibold {{ $quote->number ? 'text-accent' : 'italic text-muted' }}">{{ $quote->number ?? 'Brouillon' }}</span>
                                        <span class="text-muted">· {{ $quote->issue_date->format('d/m/Y') }}</span>
                                    </span>
                                    <span class="flex items-center gap-3">
                                        <span class="font-semibold tabular-nums">{{ Money::format($quote->total_ttc, true) }}</span>
                                        <x-badge :status="$status" />
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section class="card">
                <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-xl font-bold">Factures</h2>
                    @unless ($client->isArchived())
                        <a href="{{ route('invoices.create', ['client' => $client->id]) }}" class="btn-secondary">+ Nouvelle facture</a>
                    @endunless
                </div>

                @if ($invoices->isEmpty())
                    <p class="text-muted">Aucune facture pour ce client.</p>
                @else
                    <ul class="-mx-5 divide-y divide-line border-t border-line sm:-mx-7">
                        @foreach ($invoices as $invoice)
                            @php($status = $invoice->displayStatus())
                            <li>
                                <a href="{{ route('invoices.show', $invoice) }}"
                                   class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 px-5 py-3 list-row sm:px-7">
                                    <span>
                                        <span class="font-semibold {{ $invoice->number ? 'text-accent' : 'italic text-muted' }}">{{ $invoice->number ?? 'Brouillon' }}</span>
                                        <span class="text-muted">· {{ $invoice->issue_date->format('d/m/Y') }}</span>
                                    </span>
                                    <span class="flex items-center gap-3">
                                        <span class="font-semibold tabular-nums">{{ Money::format($invoice->total_ttc, true) }}</span>
                                        <x-badge :status="$status" />
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>
    </div>

    <section class="card mt-8">
        <h2 class="card-title">Archiver ou supprimer</h2>

        <div class="flex flex-wrap items-center gap-3">
            @if ($client->isArchived())
                <form method="POST" action="{{ route('clients.restore', $client) }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn-secondary">Réactiver le client</button>
                </form>
            @else
                <form method="POST" action="{{ route('clients.archive', $client) }}"
                      data-confirm-title="Archiver {{ $client->name }} ?" data-confirm="Il ne sera plus proposé pour les nouveaux devis et factures. Ses documents restent visibles, et vous pourrez le réactiver." data-confirm-label="Oui, archiver">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn-secondary">Archiver le client</button>
                </form>
            @endif

            @if ($client->hasDocuments())
                <p class="text-muted">Ce client a des devis ou des factures : il ne peut pas être supprimé, seulement archivé.</p>
            @else
                <form method="POST" action="{{ route('clients.destroy', $client) }}"
                      data-confirm-title="Supprimer {{ $client->name }} ?" data-confirm="Le client sera supprimé définitivement." data-confirm-label="Oui, supprimer" data-confirm-danger>
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-danger">Supprimer le client</button>
                </form>
            @endif
        </div>
    </section>
</x-app-layout>
