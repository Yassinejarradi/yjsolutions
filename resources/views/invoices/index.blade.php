<x-app-layout title="Factures">
    <x-slot name="header">
        <h1 class="text-3xl font-bold">Factures</h1>
        <a href="{{ route('invoices.create') }}" class="btn-primary">+ Nouvelle facture</a>
    </x-slot>

    <livewire:invoice-list />
</x-app-layout>
