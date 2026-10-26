<x-app-layout title="Clients">
    <x-slot name="header">
        <h1 class="text-3xl font-bold">Clients</h1>
        <a href="{{ route('clients.create') }}" class="btn-primary">+ Nouveau client</a>
    </x-slot>

    <livewire:client-list />
</x-app-layout>
