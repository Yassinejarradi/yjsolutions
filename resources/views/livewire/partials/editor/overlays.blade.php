{{-- Message « Annuler », création rapide d'un client et panneau « Mise en page ».
     Variables : $documentType, $documentLabel. --}}
{{-- « Annuler » après la suppression d'une ligne --}}
@if ($removedLine)
    <div wire:key="undo-{{ $removedLine['line']['key'] }}" x-data="{ show: true }" x-init="setTimeout(() => show = false, 8000)"
         x-show="show" x-transition.opacity role="status"
         class="fixed bottom-6 left-1/2 z-40 flex -translate-x-1/2 items-center gap-5 rounded-lg bg-ink px-5 py-3 text-lg text-white shadow-xl">
        <span>Ligne supprimée.</span>
        <button type="button" wire:click="undoRemove" @click="show = false" class="font-bold text-white underline underline-offset-4">Annuler</button>
    </div>
@endif

{{-- Création rapide d'un client --}}
@if ($showClientForm)
    <div class="fixed inset-0 z-50 flex items-end justify-center bg-ink/50 p-4 sm:items-center" data-no-autosave
         @keydown.escape.window="$wire.set('showClientForm', false)">
        <form wire:submit="createClient" class="card w-full max-w-lg" role="dialog" aria-labelledby="new-client-title">
            <h2 id="new-client-title" class="card-title">Nouveau client</h2>
            <div class="space-y-4">
                @foreach (['name' => 'Raison sociale', 'address' => 'Adresse', 'city' => 'Ville', 'ice' => 'ICE (15 chiffres)'] as $field => $label)
                    <div>
                        <label for="new-client-{{ $field }}" class="field-label">
                            {{ $label }}@if ($field === 'name')<span class="text-accent" aria-hidden="true">*</span>@endif
                        </label>
                        <input id="new-client-{{ $field }}" wire:model="newClient.{{ $field }}" class="field-input" autocomplete="off"
                               @if ($field === 'name') autofocus @endif @if ($field === 'ice') inputmode="numeric" @endif>
                        @error("newClient.$field")<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                @endforeach
            </div>
            <p class="field-help mt-3">IF, RC, contact et délai de paiement se complètent ensuite dans la fiche du client.</p>
            <div class="mt-6 flex flex-wrap gap-3">
                <button type="submit" class="btn-primary">Créer et choisir ce client</button>
                <button type="button" class="btn-secondary" wire:click="$set('showClientForm', false)">Annuler</button>
            </div>
        </form>
    </div>
@endif

@include('livewire.partials.layout-panel')
