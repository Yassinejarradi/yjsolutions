<x-app-layout title="Paramètres">
    <x-settings-tabs />

    <form method="POST" action="{{ route('settings.documents.update') }}" class="space-y-8"
          x-data="{ invoicePrefix: @js(old('invoice_prefix', $setting->invoice_prefix)), quotePrefix: @js(old('quote_prefix', $setting->quote_prefix)) }">
        @csrf
        @method('PUT')

        <section class="card">
            <h2 class="card-title">Numérotation</h2>
            <p class="-mt-3 mb-6 text-muted">
                Les numéros se suivent sans trou et repartent à 001 chaque année.
                Ils sont attribués au moment de l’émission, jamais aux brouillons.
            </p>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <x-field name="invoice_prefix" label="Préfixe des factures" :value="$setting->invoice_prefix" required maxlength="10" x-model="invoicePrefix" />
                    <p class="field-help">Exemple : <strong class="text-accent" x-text="(invoicePrefix || '').toUpperCase() + '{{ now()->year }}-001'"></strong></p>
                </div>
                <div>
                    <x-field name="quote_prefix" label="Préfixe des devis" :value="$setting->quote_prefix" required maxlength="10" x-model="quotePrefix" />
                    <p class="field-help">Exemple : <strong class="text-accent" x-text="(quotePrefix || '').toUpperCase() + '{{ now()->year }}-001'"></strong></p>
                </div>
            </div>
        </section>

        <section class="card">
            <h2 class="card-title">Valeurs par défaut</h2>
            <p class="-mt-3 mb-6 text-muted">Proposées à la création d’un nouveau document, et modifiables ensuite document par document.</p>

            <div class="grid gap-5 sm:grid-cols-3">
                <div>
                    <label for="default_vat_rate" class="field-label">TVA par défaut</label>
                    <select id="default_vat_rate" name="default_vat_rate" class="field-input">
                        @foreach ($vatRates as $rate)
                            <option value="{{ $rate }}" @selected((int) old('default_vat_rate', $setting->default_vat_rate) === $rate)>
                                {{ $rate }} %{{ $rate === 0 ? ' (exonéré)' : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('default_vat_rate')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <x-field name="payment_terms_days" label="Délai de paiement (jours)" type="number" min="0" max="365"
                         :value="$setting->payment_terms_days" required help="Date d’échéance = date de facture + ce délai." />
                <x-field name="quote_validity_days" label="Validité des devis (jours)" type="number" min="1" max="365"
                         :value="$setting->quote_validity_days" required />
            </div>
        </section>

        <section class="card">
            <h2 class="card-title">Textes par défaut</h2>

            <div class="space-y-5">
                <x-field name="quote_payment_terms" label="Modalités de paiement des devis" :value="$setting->quote_payment_terms"
                         placeholder="Ex. : 50 % à la commande, solde à la livraison" />
                <x-field name="quote_completion_delay" label="Délai de réalisation des devis" :value="$setting->quote_completion_delay"
                         placeholder="Ex. : 3 semaines à compter de l’accord" />
                <p class="field-help -mt-3">Ces deux textes sont proposés sur chaque nouveau devis et restent modifiables devis par devis.</p>
                <x-field name="invoice_notes" label="Mentions en bas des factures" type="textarea" :value="$setting->invoice_notes"
                         placeholder="Ex. : Tout retard de paiement entraîne des pénalités…" />
                <x-field name="quote_notes" label="Mentions en bas des devis" type="textarea" :value="$setting->quote_notes" />
            </div>
        </section>

        <button type="submit" class="btn-primary px-8 py-3 text-lg">Enregistrer</button>
    </form>
</x-app-layout>
