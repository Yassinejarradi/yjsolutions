{{--
    Panneau « Mise en page » de l'éditeur (devis, et facture en phase 4) : logo, éléments affichés,
    informations de la société sur ce document, modèle des prochains documents.
    Variables : $layout, $logoUrl, $layoutMessage, $documentType (« quote », « invoice »), $documentLabel (« devis », « factures »).
--}}
@php
    use App\Support\DocumentLayout;
@endphp

<div x-show="layoutPanel" x-cloak class="fixed inset-0 z-50 flex justify-end" role="dialog" aria-labelledby="layout-panel-title"
     @keydown.escape.window="layoutPanel = false">
    <div class="absolute inset-0 bg-ink/40" @click="layoutPanel = false" x-show="layoutPanel" x-transition.opacity></div>

    <div class="relative flex h-full w-full max-w-md flex-col bg-white shadow-2xl" x-show="layoutPanel"
         x-transition:enter="transition duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0">
        <div class="flex items-center justify-between border-b border-line px-5 py-4">
            <h2 id="layout-panel-title" class="text-xl font-bold">Mise en page</h2>
            <button type="button" class="btn-secondary px-4 py-2" @click="layoutPanel = false">Fermer</button>
        </div>

        <div class="flex-1 space-y-8 overflow-y-auto px-5 py-6">
            @if ($layoutMessage)
                <p role="status" class="rounded-lg border-l-4 border-green-600 bg-green-50 px-4 py-3 text-green-900">{{ $layoutMessage }}</p>
            @endif

            <p class="text-muted">
                Les textes (titre, libellés, en-têtes du tableau, signatures…) se modifient directement sur la feuille : cliquez dessus.
            </p>

            {{-- Logo --}}
            <section>
                <h3 class="mb-3 text-lg font-bold">Logo</h3>
                <div class="flex items-center gap-4">
                    <div class="flex h-24 w-24 shrink-0 items-center justify-center rounded-lg border border-line bg-white p-1.5">
                        @if ($logoUrl)
                            <img src="{{ $logoUrl }}" alt="Logo actuel" class="max-h-full max-w-full">
                        @else
                            <span class="text-center text-sm text-muted">Pas de logo</span>
                        @endif
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="btn-secondary cursor-pointer">
                            <input type="file" wire:model="logoUpload" accept="image/png,image/jpeg" class="sr-only">
                            {{ $layout['logo']['mode'] === 'hidden' ? 'Ajouter un logo…' : 'Remplacer le logo…' }}
                        </label>
                        @if ($layout['logo']['mode'] === 'hidden')
                            <button type="button" class="btn-secondary" wire:click="setLogoMode('default')">Remettre le logo de la société</button>
                        @else
                            @if ($layout['logo']['mode'] === 'custom')
                                <button type="button" class="btn-secondary" wire:click="setLogoMode('default')">Reprendre le logo de la société</button>
                            @endif
                            <button type="button" class="btn-secondary text-accent" wire:click="setLogoMode('hidden')">Retirer le logo</button>
                        @endif
                    </div>
                </div>
                <p wire:loading wire:target="logoUpload" class="field-help">Envoi de l’image…</p>
                @error('logoUpload')<p class="field-error">{{ $message }}</p>@enderror

                @if ($layout['logo']['mode'] !== 'hidden')
                    <div class="mt-4">
                        <p class="field-label">Taille</p>
                        <div class="inline-flex overflow-hidden rounded-lg border border-line" role="group" aria-label="Taille du logo">
                            @foreach (['small' => 'Petit', 'medium' => 'Moyen', 'large' => 'Grand'] as $size => $label)
                                <button type="button" wire:click="setLogoSize('{{ $size }}')"
                                        aria-pressed="{{ $layout['logo']['size'] === $size ? 'true' : 'false' }}"
                                        @class([
                                            'px-4 py-2 font-semibold',
                                            'bg-brand text-white' => $layout['logo']['size'] === $size,
                                            'bg-white hover:bg-brand-soft' => $layout['logo']['size'] !== $size,
                                        ])>{{ $label }}</button>
                            @endforeach
                        </div>
                    </div>
                @endif
            </section>

            {{-- Éléments affichés --}}
            <section>
                <h3 class="mb-3 text-lg font-bold">Éléments affichés</h3>
                <div class="space-y-2">
                    @foreach (DocumentLayout::blocks($documentType) as $block => $label)
                        <label class="flex items-center gap-3 rounded-lg px-2 py-1.5 text-lg hover:bg-brand-soft">
                            <input type="checkbox" wire:model.live="layout.show.{{ $block }}" class="h-5 w-5 rounded border-line text-brand focus:ring-brand">
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
            </section>

            {{-- Société sur ce document --}}
            <section>
                <h3 class="mb-1 text-lg font-bold">Société sur ce document</h3>
                <p class="mb-4 text-sm text-muted">
                    Imprimées dans le pied de page. Modifiées ici, elles ne changent que ce document ;
                    un champ vidé n’est pas imprimé. Pour toujours les changer : Paramètres › Société.
                </p>
                <div class="space-y-3">
                    @foreach (DocumentLayout::COMPANY_FIELDS as $field => $label)
                        <div>
                            <label for="layout-company-{{ $field }}" class="field-label">{{ $label }}</label>
                            <input id="layout-company-{{ $field }}" wire:model.live.debounce.800ms="layout.company.{{ $field }}" class="field-input">
                        </div>
                    @endforeach
                </div>
                <button type="button" class="btn-link mt-3" wire:click="resetCompany">Reprendre les informations des Paramètres</button>
            </section>

            {{-- Modèle --}}
            <section class="rounded-lg bg-desk p-4">
                <h3 class="mb-2 text-lg font-bold">Pour les prochains {{ $documentLabel }}</h3>
                <p class="mb-4 text-sm text-muted">
                    Le logo, les textes et les éléments affichés de ce document peuvent devenir la mise en page par défaut.
                    Les {{ $documentLabel }} déjà envoyés ne changent pas.
                </p>
                <div class="flex flex-col gap-2">
                    <button type="button" class="btn-primary" @click="$dispatch('confirm-action', { title: @js('Utiliser cette mise en page pour tous les prochains '.$documentLabel.' ?'), message: @js('Les '.$documentLabel.' déjà envoyés ne changent pas.'), confirmLabel: 'Oui, l’utiliser', onConfirm: () => $wire.saveLayoutAsTemplate() })">
                        Utiliser pour les prochains {{ $documentLabel }}
                    </button>
                    <button type="button" class="btn-secondary" @click="$dispatch('confirm-action', { title: 'Revenir à la mise en page par défaut ?', message: 'Les textes, le logo et les éléments affichés de ce document reprennent les réglages par défaut.', confirmLabel: 'Oui, rétablir', onConfirm: () => $wire.resetLayout() })">
                        Rétablir la mise en page par défaut
                    </button>
                </div>
            </section>
        </div>
    </div>
</div>
