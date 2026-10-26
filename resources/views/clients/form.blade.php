<x-app-layout :title="$client->exists ? 'Modifier '.$client->name : 'Nouveau client'">
    <form method="POST" class="space-y-8"
          action="{{ $client->exists ? route('clients.update', $client) : route('clients.store') }}">
        @csrf
        @if ($client->exists)
            @method('PUT')
        @endif

        <section class="card">
            <h2 class="card-title">Identité</h2>
            <p class="-mt-3 mb-6 text-muted">Seule la raison sociale est obligatoire. L’adresse et l’ICE sont imprimés sur les devis et les factures.</p>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-field name="name" label="Raison sociale" :value="$client->name" required class="sm:col-span-2" autocomplete="off" />
                <x-field name="address" label="Adresse" :value="$client->address" class="sm:col-span-2" />
                <x-field name="city" label="Ville" :value="$client->city" />
            </div>
        </section>

        <section class="card">
            <h2 class="card-title">Identifiants légaux</h2>

            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <x-field name="ice" label="ICE" :value="$client->ice" inputmode="numeric" help="15 chiffres" />
                <x-field name="if_number" label="IF (Identifiant fiscal)" :value="$client->if_number" />
                <x-field name="rc" label="RC (Registre du commerce)" :value="$client->rc" />
            </div>
        </section>

        <section class="card">
            <h2 class="card-title">Contact</h2>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-field name="contact_name" label="Contact principal" :value="$client->contact_name" help="Nom de la personne à contacter." />
                <x-field name="phone" label="Téléphone" type="tel" :value="$client->phone" />
                <x-field name="email" label="Email" type="email" :value="$client->email" />
            </div>
        </section>

        <section class="card">
            <h2 class="card-title">Facturation</h2>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-field name="payment_terms_days" label="Délai de paiement (jours)" type="number" min="0" max="365"
                         :value="$client->payment_terms_days" :placeholder="$defaultTerms"
                         help="Laissez vide pour utiliser le délai des Paramètres ({{ $defaultTerms }} jours)." />
                <x-field name="notes" label="Notes" type="textarea" :value="$client->notes" class="sm:col-span-2"
                         help="Pour vous seulement : ces notes ne sont jamais imprimées." />
            </div>
        </section>

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="btn-primary px-8 py-3 text-lg">{{ $client->exists ? 'Enregistrer' : 'Créer le client' }}</button>
            <a href="{{ $client->exists ? route('clients.show', $client) : route('clients.index') }}" class="btn-secondary px-8 py-3 text-lg">Annuler</a>
        </div>
    </form>
</x-app-layout>
