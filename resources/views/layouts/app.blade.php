<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ? $title.' – ' : '' }}JY Solutions</title>
    <link rel="icon" href="{{ route('logo') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body x-data="{ menuOpen: false }" @keydown.escape.window="menuOpen = false">

    {{-- Barre du haut, seulement sur téléphone et tablette --}}
    <div class="sticky top-0 z-30 flex items-center justify-between border-b border-slate-200/70 bg-white/85 px-4 py-3 backdrop-blur-md lg:hidden">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
            <img src="{{ route('logo') }}" alt="" class="h-10 w-auto">
            <span class="text-lg font-bold">JY Solutions</span>
        </a>
        <button type="button" class="btn-secondary px-4 py-2" @click="menuOpen = true" aria-controls="sidebar" :aria-expanded="menuOpen">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
            Menu
        </button>
    </div>

    {{-- Fond sombre derrière le menu ouvert sur téléphone --}}
    <div x-cloak x-show="menuOpen" x-transition.opacity class="fixed inset-0 z-40 bg-navy-deep/60 backdrop-blur-sm lg:hidden" @click="menuOpen = false"></div>

    @include('layouts.sidebar')

    <div class="lg:pl-72">
        <main class="mx-auto max-w-6xl px-4 py-6 sm:px-8 sm:py-10">
            @isset($header)
                <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
                    {{ $header }}
                </div>
            @elseif ($title)
                <h1 class="mb-8 text-3xl font-extrabold text-ink sm:text-4xl">{{ $title }}</h1>
            @endisset

            <x-flash />

            {{ $slot }}
        </main>
    </div>

    @livewireScriptConfig
</body>
</html>
