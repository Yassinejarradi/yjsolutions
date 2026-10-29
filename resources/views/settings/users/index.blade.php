<x-app-layout title="Paramètres">
    <x-settings-tabs />

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <p class="text-lg text-muted">Toute personne ayant un compte peut utiliser l’ensemble de l’application.</p>
        <a href="{{ route('settings.users.create') }}" class="btn-primary">+ Nouvel utilisateur</a>
    </div>

    <div class="card overflow-hidden p-0 sm:p-0">
        <ul class="divide-y divide-line">
            @foreach ($users as $user)
                <li class="flex flex-wrap items-center justify-between gap-4 px-5 py-4 sm:px-7">
                    <div>
                        <p class="text-lg font-semibold">
                            {{ $user->name }}
                            @if ($user->is(auth()->user()))
                                <span class="ml-2 rounded bg-brand-soft px-2 py-0.5 text-sm font-semibold text-brand">Vous</span>
                            @endif
                        </p>
                        <p class="text-muted">{{ $user->email }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('settings.users.edit', $user) }}" class="btn-secondary">Modifier</a>
                        @unless ($user->is(auth()->user()))
                            <form method="POST" action="{{ route('settings.users.destroy', $user) }}"
                                  data-confirm-title="Supprimer le compte de {{ $user->name }} ?" data-confirm="Cette personne ne pourra plus se connecter. Les devis et factures ne sont pas touchés." data-confirm-label="Oui, supprimer le compte" data-confirm-danger>
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-danger">Supprimer</button>
                            </form>
                        @endunless
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
</x-app-layout>
