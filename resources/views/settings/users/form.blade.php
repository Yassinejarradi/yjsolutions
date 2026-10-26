<x-app-layout title="Paramètres">
    <x-settings-tabs />

    <div class="card max-w-2xl">
        <h2 class="card-title">{{ $user->exists ? 'Modifier '.$user->name : 'Nouvel utilisateur' }}</h2>

        <form method="POST" class="space-y-5"
              action="{{ $user->exists ? route('settings.users.update', $user) : route('settings.users.store') }}">
            @csrf
            @if ($user->exists)
                @method('PUT')
            @endif

            <x-field name="name" label="Nom" :value="$user->name" required autocomplete="off" />
            <x-field name="email" label="Adresse email" type="email" :value="$user->email" required autocomplete="off"
                     help="Sert d’identifiant pour se connecter." />
            <x-field name="password" label="{{ $user->exists ? 'Nouveau mot de passe' : 'Mot de passe' }}" type="password"
                     :required="! $user->exists" autocomplete="new-password"
                     help="{{ $user->exists ? 'Laissez vide pour garder le mot de passe actuel. ' : '' }}8 caractères minimum." />
            <x-field name="password_confirmation" label="Confirmer le mot de passe" type="password"
                     :required="! $user->exists" autocomplete="new-password" />

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="btn-primary">{{ $user->exists ? 'Enregistrer' : 'Créer l’utilisateur' }}</button>
                <a href="{{ route('settings.users.index') }}" class="btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
</x-app-layout>
