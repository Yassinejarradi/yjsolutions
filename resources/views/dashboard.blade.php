@php
    use App\Support\Money;

    $hour = now()->hour;
    $greeting = $hour < 18 ? 'Bonjour' : 'Bonsoir';
    $today = today()->locale('fr');
    $monthName = $today->isoFormat('MMMM');
    $ofMonth = (in_array($monthName[0], ['a', 'o'], true) ? 'd’' : 'de ').$monthName; // d’avril, d’août, d’octobre

    $figures = [
        ['label' => 'Chiffre d’affaires '.$ofMonth, 'value' => $monthRevenue, 'hint' => 'HT, factures émises ce mois-ci'],
        ['label' => 'Chiffre d’affaires '.$today->year, 'value' => $yearRevenue, 'hint' => 'HT, depuis le 1er janvier'],
        ['label' => 'Encaissé en '.$today->year, 'value' => $yearCollected, 'hint' => 'TTC, paiements reçus'],
    ];

    $maxMonth = max(1, max(array_column($monthly, 'total')));
    $chartTotal = array_sum(array_column($monthly, 'total'));

    $stats = [
        [
            'label' => 'Factures en retard',
            'value' => $overdueCount,
            'hint' => $overdueCount ? 'échéance dépassée' : 'aucun retard',
            'url' => route('invoices.index', ['statut' => 'overdue']),
            'tone' => $overdueCount ? 'from-red-500 to-accent' : 'from-emerald-500 to-teal-600',
            'icon' => 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z',
        ],
        [
            'label' => 'Devis en attente',
            'value' => $pendingQuotes,
            'hint' => 'envoyés, sans réponse',
            'url' => route('quotes.index', ['statut' => 'sent']),
            'tone' => 'from-amber-400 to-orange-500',
            'icon' => 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z',
        ],
    ];

    $actions = [
        ['label' => 'Nouveau devis', 'text' => 'Préparer une proposition pour un client', 'url' => route('quotes.create'), 'icon' => 'M12 4.5v15m7.5-7.5h-15'],
        ['label' => 'Nouvelle facture', 'text' => 'Facturer une prestation réalisée', 'url' => route('invoices.create'), 'icon' => 'M12 4.5v15m7.5-7.5h-15'],
        ['label' => 'Nouveau client', 'text' => 'Ajouter une entreprise à vos contacts', 'url' => route('clients.create'), 'icon' => 'M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z'],
        ['label' => 'Informations de la société', 'text' => 'Adresse, ICE, banque et logo', 'url' => route('settings.company'), 'icon' => 'M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z'],
    ];
@endphp

<x-app-layout title="Tableau de bord">
    <x-slot name="header">
        <div class="relative w-full overflow-hidden rounded-2xl bg-brand-gradient px-5 py-5 text-white shadow-lift sm:px-7 sm:py-6">
            <div class="pointer-events-none absolute -right-16 -top-20 h-48 w-48 rounded-full bg-white/10 blur-2xl" aria-hidden="true"></div>
            <div class="pointer-events-none absolute -bottom-24 right-1/3 h-56 w-56 rounded-full bg-accent/25 blur-3xl" aria-hidden="true"></div>

            <div class="relative flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-sm font-medium capitalize text-white/70">{{ $today->isoFormat('dddd D MMMM YYYY') }}</p>
                    <h1 class="mt-0.5 text-2xl font-extrabold">{{ $greeting }} {{ auth()->user()->name }}</h1>
                    <p class="mt-1 max-w-xl text-white/80">Que voulez-vous faire aujourd’hui&nbsp;?</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('quotes.create') }}" class="btn bg-white text-brand shadow-lg hover:bg-brand-soft">+ Nouveau devis</a>
                    <a href="{{ route('invoices.create') }}" class="btn bg-white/15 text-white ring-1 ring-inset ring-white/30 backdrop-blur hover:bg-white/25">+ Nouvelle facture</a>
                </div>
            </div>
        </div>
    </x-slot>

    {{-- Chiffre d'affaires --}}
    <h2 class="mb-3 text-lg font-bold">Chiffre d’affaires</h2>
    <div class="mb-4 grid gap-4 sm:grid-cols-3">
        @foreach ($figures as $figure)
            <div class="card">
                <p class="text-muted">{{ $figure['label'] }}</p>
                <p class="mt-1 truncate text-xl font-extrabold tabular-nums text-ink">{{ Money::format($figure['value'], true) }}</p>
                <p class="text-sm text-muted">{{ $figure['hint'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- Graphique : CA HT des 12 derniers mois --}}
    <section class="card mb-8" aria-labelledby="chart-title">
        <div class="mb-4 flex flex-wrap items-baseline justify-between gap-2">
            <h3 id="chart-title" class="font-bold text-ink">Chiffre d’affaires HT sur 12 mois</h3>
            <p class="text-sm text-muted">Total&nbsp;: <span class="font-semibold text-ink tabular-nums">{{ Money::format($chartTotal, true) }}</span></p>
        </div>

        <div class="flex h-48 items-end gap-1 border-b border-line sm:gap-2" aria-hidden="true">
            @foreach ($monthly as $point)
                @php($current = $loop->last)
                @php($height = $point['total'] > 0 ? max(2, round($point['total'] / $maxMonth * 100)) : 0)
                <div class="group flex h-full min-w-0 flex-1 flex-col items-center justify-end"
                     title="{{ ucfirst($point['month']->locale('fr')->isoFormat('MMMM YYYY')) }} : {{ Money::format($point['total'], true) }} HT">
                    @if ($point['total'] > 0)
                        <span class="mb-1 hidden whitespace-nowrap text-xs font-semibold tabular-nums text-muted sm:block">{{ Money::compact($point['total']) }}</span>
                    @endif
                    <span @class([
                        'w-full max-w-[3rem] rounded-t-md transition',
                        'bg-gradient-to-t from-brand to-brand-light' => $current,
                        'bg-brand/25 group-hover:bg-brand/50' => ! $current,
                    ]) style="height: {{ $height }}%"></span>
                </div>
            @endforeach
        </div>
        <div class="mt-2 flex gap-1 sm:gap-2" aria-hidden="true">
            @foreach ($monthly as $point)
                <span @class(['min-w-0 flex-1 whitespace-nowrap text-center text-[11px] tracking-tight sm:text-xs sm:tracking-normal', 'font-bold text-brand' => $loop->last, 'text-muted' => ! $loop->last])>
                    {{ rtrim($point['month']->locale('fr')->isoFormat('MMM'), '.') }}
                </span>
            @endforeach
        </div>

        {{-- Les mêmes chiffres, lisibles par un lecteur d'écran --}}
        <table class="sr-only">
            <caption>Chiffre d’affaires HT par mois</caption>
            <tbody>
                @foreach ($monthly as $point)
                    <tr><th scope="row">{{ $point['month']->locale('fr')->isoFormat('MMMM YYYY') }}</th><td>{{ Money::format($point['total'], true) }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </section>

    {{-- À suivre --}}
    <h2 class="mb-3 text-lg font-bold">À suivre</h2>
    <div class="mb-8 grid gap-4 sm:grid-cols-2">
        @foreach ($stats as $stat)
            <a href="{{ $stat['url'] }}" class="card group flex items-start gap-4 transition hover:-translate-y-0.5 hover:shadow-lift">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br text-white shadow-md {{ $stat['tone'] }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $stat['icon'] }}" /></svg>
                </span>
                <span class="min-w-0">
                    <span class="block text-muted">{{ $stat['label'] }}</span>
                    <span class="block truncate text-xl font-extrabold tabular-nums text-ink">{{ $stat['value'] }}</span>
                    <span class="block text-sm text-muted">{{ $stat['hint'] }}</span>
                </span>
            </a>
        @endforeach
    </div>

    {{-- Actions rapides --}}
    <h2 class="mb-3 text-lg font-bold">Actions rapides</h2>
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($actions as $action)
            <a href="{{ $action['url'] }}" class="card group flex flex-col gap-3 transition hover:-translate-y-0.5 hover:border-brand/30 hover:shadow-lift">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-soft text-brand transition group-hover:bg-brand group-hover:text-white">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $action['icon'] }}" /></svg>
                </span>
                <span>
                    <span class="block font-bold text-ink">{{ $action['label'] }}</span>
                    <span class="mt-0.5 block text-sm text-muted">{{ $action['text'] }}</span>
                </span>
                <span class="mt-auto text-sm font-semibold text-brand">Ouvrir →</span>
            </a>
        @endforeach
    </div>
</x-app-layout>
