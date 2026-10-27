{{-- Messages de confirmation ou d'erreur après une action. Restent affichés jusqu'à « Fermer ». --}}
@php
    $styles = [
        'success' => ['ring-emerald-200 bg-emerald-50 text-emerald-900', 'bg-emerald-500', 'M4.5 12.75l6 6 9-13.5'],
        'error' => ['ring-red-200 bg-red-50 text-red-900', 'bg-accent', 'M12 9v3.75m0 3.75h.008v.008H12v-.008zM21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
    ];
@endphp

@foreach ($styles as $type => [$box, $dot, $icon])
    @if (session($type))
        <div x-data="{ show: true }" x-show="show" x-transition.opacity role="{{ $type === 'error' ? 'alert' : 'status' }}"
             class="mb-6 flex items-start gap-4 rounded-2xl px-5 py-4 text-lg shadow-soft ring-1 {{ $box }}">
            <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-white {{ $dot }}" aria-hidden="true">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" /></svg>
            </span>
            <p class="flex-1 pt-0.5">{{ session($type) }}</p>
            <button type="button" class="shrink-0 rounded-lg px-2 py-1 text-base font-semibold underline-offset-4 hover:underline" @click="show = false">Fermer</button>
        </div>
    @endif
@endforeach

@if ($errors->any())
    <div role="alert" class="mb-6 flex items-start gap-4 rounded-2xl bg-red-50 px-5 py-4 text-lg text-red-900 shadow-soft ring-1 ring-red-200">
        <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-accent text-white" aria-hidden="true">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008z" /></svg>
        </span>
        <p class="pt-0.5">Le formulaire contient des erreurs. Corrigez les champs indiqués en rouge.</p>
    </div>
@endif
