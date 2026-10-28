<x-app-layout title="Paramètres">
    <x-settings-tabs />

    <div class="card mb-6">
        <h2 class="card-title">Sauvegardes</h2>
        <p class="mb-4 max-w-3xl text-muted">
            Une sauvegarde contient tous les clients, devis, factures et paiements, ainsi que les logos.
            Elle est faite automatiquement chaque jour ; les {{ config('backup.keep') }} dernières sont gardées.
            Téléchargez-en une de temps en temps et rangez-la ailleurs (clé USB, Google Drive, OneDrive).
        </p>
        <form method="POST" action="{{ route('settings.backups.store') }}">
            @csrf
            <button type="submit" class="btn-primary">Faire une sauvegarde maintenant</button>
        </form>
    </div>

    <div class="card overflow-hidden p-0 sm:p-0">
        @if ($backups === [])
            <p class="px-5 py-8 text-center text-muted">Aucune sauvegarde pour l’instant.</p>
        @else
            <ul class="divide-y divide-line">
                @foreach ($backups as $backup)
                    <li class="flex flex-wrap items-center justify-between gap-3 px-5 py-3 sm:px-6">
                        <div>
                            <p class="font-semibold">
                                Sauvegarde du {{ $backup['date']->format('d/m/Y') }} à {{ $backup['date']->format('H:i') }}
                                @if ($loop->first)
                                    <span class="ml-2 rounded bg-brand-soft px-2 py-0.5 text-sm font-semibold text-brand">La plus récente</span>
                                @endif
                            </p>
                            <p class="text-sm text-muted">{{ number_format($backup['size'] / 1024, 0, ',', ' ') }} Ko</p>
                        </div>
                        <a href="{{ route('settings.backups.download', $backup['name']) }}" class="btn-secondary">Télécharger</a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</x-app-layout>
