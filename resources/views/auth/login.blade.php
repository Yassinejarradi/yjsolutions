<x-guest-layout>
    <h1 class="text-3xl font-extrabold">Connexion</h1>
    <p class="mb-8 mt-2 text-lg text-muted">Connectez-vous pour accéder à vos devis et factures.</p>

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <x-field name="email" label="Adresse email" type="email" required autofocus autocomplete="username" />

        <x-field name="password" label="Mot de passe" type="password" required autocomplete="current-password" />

        <label for="remember_me" class="flex items-center gap-3">
            <input id="remember_me" type="checkbox" name="remember" class="h-5 w-5 rounded border-line text-brand focus:ring-brand">
            <span>Rester connecté sur cet appareil</span>
        </label>

        <button type="submit" class="btn-primary w-full py-3 text-lg">Se connecter</button>
    </form>

    <p class="mt-6 text-sm text-muted">
        Mot de passe oublié ? Demandez à l’administrateur de le réinitialiser.
    </p>
</x-guest-layout>
