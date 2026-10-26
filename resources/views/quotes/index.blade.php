<x-app-layout title="Devis">
    <x-slot name="header">
        <h1 class="text-3xl font-bold">Devis</h1>
        <a href="{{ route('quotes.create') }}" class="btn-primary">+ Nouveau devis</a>
    </x-slot>

    <livewire:quote-list />
</x-app-layout>
