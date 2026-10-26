{{-- Messages de confirmation ou d'erreur après une action. Restent affichés jusqu'à « Fermer ». --}}
@foreach (['success' => 'border-green-600 bg-green-50 text-green-900', 'error' => 'border-accent bg-red-50 text-red-900'] as $type => $classes)
    @if (session($type))
        <div x-data="{ show: true }" x-show="show" role="{{ $type === 'error' ? 'alert' : 'status' }}"
             class="mb-6 flex items-start justify-between gap-4 rounded-lg border-l-4 px-5 py-4 text-lg {{ $classes }}">
            <p>{{ session($type) }}</p>
            <button type="button" class="shrink-0 text-base font-semibold underline" @click="show = false">Fermer</button>
        </div>
    @endif
@endforeach

@if ($errors->any())
    <div role="alert" class="mb-6 rounded-lg border-l-4 border-accent bg-red-50 px-5 py-4 text-lg text-red-900">
        Le formulaire contient des erreurs. Corrigez les champs indiqués en rouge.
    </div>
@endif
