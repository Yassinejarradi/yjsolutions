<x-app-layout title="Tableau de bord">
    <x-slot name="header">
        <div>
            <h1 class="text-3xl font-bold">Bonjour {{ auth()->user()->name }}</h1>
            <p class="mt-1 text-lg text-muted">{{ now()->locale('fr')->isoFormat('dddd D MMMM YYYY') }}</p>
        </div>
    </x-slot>

    {{-- Les indicateurs (chiffre d'affaires, impayés, retards, devis en attente, graphique) arrivent en phase 5. --}}
    <div class="card">
        <h2 class="card-title">Bienvenue dans JY Solutions Gestion</h2>
        <p class="mb-6 text-lg text-muted">
            Commencez par vérifier les informations de la société : elles apparaissent sur tous vos devis et factures.
        </p>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('settings.company') }}" class="btn-primary">Vérifier les informations de la société</a>
            <a href="{{ route('settings.users.index') }}" class="btn-secondary">Gérer les utilisateurs</a>
        </div>
    </div>
</x-app-layout>
