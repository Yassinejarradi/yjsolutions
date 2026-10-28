{{-- Onglets de la page Paramètres --}}
@php
    $tabs = [
        ['route' => 'settings.company', 'active' => 'settings.company*', 'label' => 'Société'],
        ['route' => 'settings.documents', 'active' => 'settings.documents*', 'label' => 'Devis et factures'],
        ['route' => 'settings.users.index', 'active' => 'settings.users.*', 'label' => 'Utilisateurs'],
        ['route' => 'settings.backups', 'active' => 'settings.backups*', 'label' => 'Sauvegardes'],
    ];
@endphp

<nav class="mb-6 inline-flex max-w-full flex-wrap gap-1 rounded-xl bg-white p-1 shadow-soft ring-1 ring-slate-200/80" aria-label="Rubriques des paramètres">
    @foreach ($tabs as $tab)
        @php($active = request()->routeIs($tab['active']))
        <a href="{{ route($tab['route']) }}"
           @class([
               'rounded-lg px-4 py-2 font-semibold transition',
               'bg-gradient-to-b from-brand-light to-brand text-white shadow-brand' => $active,
               'text-muted hover:bg-slate-100 hover:text-ink' => ! $active,
           ])
           @if ($active) aria-current="page" @endif>
            {{ $tab['label'] }}
        </a>
    @endforeach
</nav>
