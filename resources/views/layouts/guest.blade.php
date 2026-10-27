<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Connexion – JY Solutions</title>
    <link rel="icon" href="{{ route('logo') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="flex min-h-screen">
        {{-- Panneau de marque (ordinateur) --}}
        <aside class="relative hidden w-[46%] overflow-hidden bg-brand-gradient p-12 text-white lg:flex lg:flex-col lg:justify-between">
            <div class="pointer-events-none absolute -left-32 top-1/3 h-96 w-96 rounded-full bg-brand-light/40 blur-3xl" aria-hidden="true"></div>
            <div class="pointer-events-none absolute -bottom-24 right-0 h-80 w-80 rounded-full bg-accent/30 blur-3xl" aria-hidden="true"></div>

            <div class="relative flex items-center gap-4">
                <span class="rounded-2xl bg-white p-2 shadow-xl">
                    <img src="{{ route('logo') }}" alt="JY Solutions" class="h-16 w-auto">
                </span>
                <span class="text-2xl font-bold leading-tight">JY Solutions<br><span class="text-lg font-medium text-white/70">Gestion</span></span>
            </div>

            <div class="relative max-w-md">
                <h2 class="text-4xl font-extrabold leading-tight">Devis, factures et clients, au même endroit.</h2>
                <ul class="mt-8 space-y-4 text-lg text-white/85">
                    @foreach (['Des documents prêts à imprimer, identiques à l’écran et en PDF', 'Les montants et la somme en lettres calculés pour vous', 'Les paiements et les retards suivis automatiquement'] as $point)
                        <li class="flex items-start gap-3">
                            <span class="mt-1 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-white/15 ring-1 ring-white/25">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            </span>
                            {{ $point }}
                        </li>
                    @endforeach
                </ul>
            </div>

            <p class="relative text-sm text-white/50">© {{ now()->year }} JY Solutions · Casablanca</p>
        </aside>

        {{-- Formulaire --}}
        <main class="flex flex-1 flex-col items-center justify-center px-4 py-10 sm:px-8">
            <img src="{{ route('logo') }}" alt="JY Solutions" class="mb-8 h-24 w-auto lg:hidden">

            <div class="w-full max-w-md rounded-3xl bg-white p-7 shadow-lift ring-1 ring-slate-200/80 sm:p-10">
                {{ $slot }}
            </div>
        </main>
    </div>

    {{-- app.js démarre Livewire : il lui faut sa configuration, comme dans layouts/app. --}}
    @livewireScriptConfig
</body>
</html>
