{{-- Barre d'actions de l'éditeur. Variables : $documentId, $routePrefix (quotes, invoices), $newDocumentHint,
     $sendLabel, $sendConfirm, $sendAction (send, issue). --}}
{{-- Barre d'actions : reste visible en haut de l'écran --}}
<div class="sticky top-[4.6rem] z-20 -mx-4 mb-6 flex flex-wrap items-center justify-between gap-3 border-b border-line bg-desk/95 px-4 py-3 backdrop-blur sm:-mx-8 sm:px-8 lg:top-0">
    <div class="flex flex-wrap items-center gap-3">
        <span class="rounded bg-white px-2.5 py-1 text-sm font-bold uppercase tracking-wide text-muted ring-1 ring-line">Brouillon</span>
        <span class="text-muted" aria-live="polite">
            <span x-show="state === 'dirty'" x-cloak>Modifications en cours…</span>
            <span x-show="state === 'saving'" x-cloak>Enregistrement…</span>
            <span x-show="state === 'error'" x-cloak class="font-semibold text-accent">Non enregistré : corrigez les champs en rouge.</span>
            <span x-show="state === 'saved' || state === 'idle'">
                @if ($savedAt)
                    <span class="font-semibold text-green-700">✓ Enregistré à {{ $savedAt }}</span>
                @else
                    {{ $newDocumentHint }}
                @endif
            </span>
        </span>
    </div>

    <div class="flex flex-wrap gap-2">
        <button type="button" class="btn-secondary" @click="layoutPanel = true">Mise en page</button>
        @if ($documentId)
            <button type="button" class="btn-secondary" @click="openAfterSave(@js(route($routePrefix.'.pdf', $documentId)))">Aperçu PDF</button>
            <button type="button" class="btn-secondary" @click="openAfterSave(@js(route($routePrefix.'.word', $documentId)), false)">Word</button>
        @endif
        <button type="button" class="btn-primary"
                wire:click="{{ $sendAction ?? 'send' }}"
                wire:confirm="{{ $sendConfirm }}"
                wire:loading.attr="disabled" wire:target="{{ $sendAction ?? 'send' }}">
            {{ $sendLabel }}
        </button>
    </div>
</div>

@if ($errors->hasAny(['send', 'clientId', 'lines']))
    <div role="alert" class="mb-6 rounded-lg border-l-4 border-accent bg-red-50 px-5 py-4 text-lg text-red-900">
        @foreach (['send', 'clientId', 'lines'] as $key)
            @error($key)<p>{{ $message }}</p>@enderror
        @endforeach
    </div>
@endif

<p class="mx-auto mb-4 max-w-[210mm] text-muted">
    Cliquez sur n’importe quel texte pour le modifier, y compris les titres et les libellés. Le logo, les éléments affichés
    et les informations de la société se règlent dans « Mise en page ». Les montants se calculent seuls.
</p>
