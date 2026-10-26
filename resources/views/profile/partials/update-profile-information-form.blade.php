<section>
    <h2 class="card-title">Mes informations</h2>

    <form method="post" action="{{ route('profile.update') }}" class="space-y-5">
        @csrf
        @method('patch')

        <x-field name="name" label="Nom" :value="$user->name" required autocomplete="name" />
        <x-field name="email" label="Adresse email" type="email" :value="$user->email" required autocomplete="username"
                 help="C’est l’identifiant utilisé pour se connecter." />

        <button type="submit" class="btn-primary">Enregistrer</button>
    </form>
</section>
