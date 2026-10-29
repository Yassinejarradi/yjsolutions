@php
    // Entrées du menu. Une entrée n'apparaît que si sa route existe :
    // les modules Devis, Factures et Clients s'ajoutent au fil des phases.
    $items = [
        ['route' => 'dashboard', 'active' => 'dashboard', 'label' => 'Tableau de bord', 'icon' => 'M2.25 12l8.954-8.955a1.126 1.126 0 011.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25'],
        ['route' => 'quotes.index', 'active' => 'quotes.*', 'label' => 'Devis', 'icon' => 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z'],
        ['route' => 'invoices.index', 'active' => 'invoices.*', 'label' => 'Factures', 'icon' => 'M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z'],
        ['route' => 'reminders', 'active' => 'reminders', 'label' => 'À relancer', 'icon' => 'M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0'],
        ['route' => 'clients.index', 'active' => 'clients.*', 'label' => 'Clients', 'icon' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z'],
        ['route' => 'settings.company', 'active' => 'settings.*', 'label' => 'Paramètres', 'icon' => 'M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z M15 12a3 3 0 11-6 0 3 3 0 016 0z'],
    ];

    // Pastille sur « À relancer » : nombre de factures en retard.
    $overdueCount = \App\Models\Invoice::query()->withDisplayStatus(\App\Enums\InvoiceStatus::Overdue)->count();
@endphp

<aside id="sidebar"
       class="fixed inset-y-0 left-0 z-50 flex w-60 -translate-x-full flex-col overflow-hidden bg-sidebar-gradient text-white shadow-2xl transition-transform duration-200 lg:translate-x-0 lg:shadow-none"
       :class="{ '-translate-x-full': !menuOpen, 'translate-x-0': menuOpen }"
       aria-label="Menu principal">

    {{-- Halo décoratif --}}
    <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-brand/40 blur-3xl" aria-hidden="true"></div>

    <div class="relative flex items-center justify-between px-5 pb-4 pt-5">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
            <span class="rounded-xl bg-white p-1 shadow-lg ring-1 ring-white/20">
                <img src="{{ route('logo') }}" alt="JY Solutions" class="h-9 w-auto">
            </span>
            <span class="font-bold leading-tight">JY Solutions<br><span class="text-sm font-medium text-white/60">Gestion</span></span>
        </a>
        <button type="button" class="rounded-lg bg-white/10 px-3 py-1.5 text-sm font-semibold hover:bg-white/20 lg:hidden" @click="menuOpen = false">Fermer</button>
    </div>

    <nav class="relative flex-1 space-y-1 overflow-y-auto px-3 py-3">
        <p class="px-3 pb-2 text-xs font-semibold uppercase tracking-widest text-white/40">Menu</p>
        @foreach ($items as $item)
            @continue(! Route::has($item['route']))
            @php($active = request()->routeIs($item['active']))
            <a href="{{ route($item['route']) }}"
               @class([
                   'group flex items-center gap-3 rounded-lg px-3 py-2 font-semibold transition',
                   'bg-white text-brand shadow-lg shadow-navy-deep/30' => $active,
                   'text-white/80 hover:bg-white/10 hover:text-white' => ! $active,
               ])
               @if ($active) aria-current="page" @endif>
                <span @class([
                    'flex h-8 w-8 shrink-0 items-center justify-center rounded-md transition',
                    'bg-brand-soft text-brand' => $active,
                    'bg-white/10 text-white group-hover:bg-white/15' => ! $active,
                ])>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" />
                    </svg>
                </span>
                {{ $item['label'] }}
                @if ($item['route'] === 'reminders' && $overdueCount)
                    <span class="ml-auto rounded-full bg-accent px-2 py-0.5 text-xs font-bold text-white" title="Factures en retard">{{ $overdueCount }}</span>
                @endif
            </a>
        @endforeach
    </nav>

    @php($user = auth()->user())
    <div class="relative m-3 rounded-xl bg-white/[0.07] p-3 ring-1 ring-white/10">
        <div class="mb-3 flex items-center gap-3">
            <x-avatar :name="$user->name" size="sm" class="ring-2 ring-white/20" />
            <span class="min-w-0">
                <span class="block truncate font-semibold">{{ $user->name }}</span>
                <span class="block truncate text-sm text-white/60">{{ $user->email }}</span>
            </span>
        </div>
        <div class="grid gap-2">
            <a href="{{ route('profile.edit') }}" class="rounded-lg bg-white/10 px-3 py-2 text-center text-sm font-semibold transition hover:bg-white/20">Mon profil</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full rounded-lg bg-white/10 px-3 py-2 text-sm font-semibold transition hover:bg-white/20">Se déconnecter</button>
            </form>
        </div>
    </div>
</aside>
