<x-app-layout title="Paramètres">
    <x-settings-tabs />

    <form method="POST" action="{{ route('settings.company.update') }}" enctype="multipart/form-data" class="space-y-8">
        @csrf
        @method('PUT')

        <section class="card">
            <h2 class="card-title">Identité de la société</h2>
            <p class="-mt-3 mb-6 text-muted">Ces informations sont imprimées en en-tête et en pied de page de chaque devis et facture.</p>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-field name="company_name" label="Raison sociale" :value="$setting->company_name" required class="sm:col-span-2" />
                <x-field name="address" label="Adresse" :value="$setting->address" required class="sm:col-span-2" />
                <x-field name="phone" label="Téléphone" :value="$setting->phone" />
                <x-field name="email" label="Email" type="email" :value="$setting->email" />
            </div>
        </section>

        <section class="card">
            <h2 class="card-title">Identifiants légaux</h2>

            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <x-field name="ice" label="ICE" :value="$setting->ice" required inputmode="numeric" help="15 chiffres" />
                <x-field name="rc" label="RC (Registre du commerce)" :value="$setting->rc" />
                <x-field name="if_number" label="IF (Identifiant fiscal)" :value="$setting->if_number" />
                <x-field name="tp" label="TP (Patente)" :value="$setting->tp" />
                <x-field name="cnss" label="CNSS" :value="$setting->cnss" />
            </div>
        </section>

        <section class="card">
            <h2 class="card-title">Coordonnées bancaires</h2>
            <p class="-mt-3 mb-6 text-muted">Imprimées sur les factures : « Paiement par virement bancaire : Banque – Agence – RIB ».</p>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-field name="bank_name" label="Banque" :value="$setting->bank_name" />
                <x-field name="bank_branch" label="Agence" :value="$setting->bank_branch" />
                <x-field name="rib" label="RIB" :value="$setting->rib" class="sm:col-span-2" inputmode="numeric" help="24 chiffres" />
            </div>
        </section>

        <section class="card">
            <h2 class="card-title">Logo</h2>

            <div class="flex flex-col gap-6 sm:flex-row sm:items-center">
                <div class="flex h-36 w-36 shrink-0 items-center justify-center rounded-lg border border-line bg-white p-2">
                    <img src="{{ route('logo') }}?v={{ $setting->updated_at?->timestamp }}" alt="Logo actuel" class="max-h-full max-w-full">
                </div>
                {{-- Bouton en français quelle que soit la langue du navigateur --}}
                <div class="flex-1" x-data="{ fileName: '' }">
                    <p class="field-label">Remplacer le logo</p>
                    <input id="logo" name="logo" type="file" accept="image/png,image/jpeg" class="sr-only"
                           @change="fileName = $event.target.files[0]?.name ?? ''">
                    <div class="flex flex-wrap items-center gap-3">
                        <label for="logo" class="btn-secondary cursor-pointer">Choisir une image…</label>
                        <span class="text-muted" x-text="fileName || 'Aucune image choisie'"></span>
                    </div>
                    <p class="field-help">Image PNG ou JPG, 2 Mo maximum. Cliquez ensuite sur « Enregistrer ».</p>
                    @error('logo')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <div class="flex flex-wrap items-center gap-4">
            <button type="submit" class="btn-primary px-8 py-3 text-lg">Enregistrer</button>
        </div>
    </form>

    @if ($setting->logo_path)
        <form method="POST" action="{{ route('settings.company.logo.destroy') }}" class="mt-4"
              data-confirm-title="Remettre le logo d’origine ?" data-confirm="Le logo téléversé sera retiré. Les documents déjà envoyés gardent leur logo." data-confirm-label="Oui, remettre l’original">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn-secondary">Rétablir le logo d’origine</button>
        </form>
    @endif
</x-app-layout>
