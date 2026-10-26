{{-- Onglets de la page Paramètres --}}
@php
    $tabs = [
        ['route' => 'settings.company', 'active' => 'settings.company*', 'label' => 'Société'],
        ['route' => 'settings.documents', 'active' => 'settings.documents*', 'label' => 'Devis et factures'],
        ['route' => 'settings.users.index', 'active' => 'settings.users.*', 'label' => 'Utilisateurs'],
    ];
@endphp

<nav class="mb-8 flex flex-wrap gap-2 border-b border-line" aria-label="Rubriques des paramètres">
    @foreach ($tabs as $tab)
        @php($active = request()->routeIs($tab['active']))
        <a href="{{ route($tab['route']) }}"
           @class([
               '-mb-px border-b-4 px-4 py-3 text-lg font-semibold',
               'border-brand text-brand' => $active,
               'border-transparent text-muted hover:text-ink' => ! $active,
           ])
           @if ($active) aria-current="page" @endif>
            {{ $tab['label'] }}
        </a>
    @endforeach
</nav>
