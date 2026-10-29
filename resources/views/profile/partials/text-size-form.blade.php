<section>
    <h2 class="card-title">Taille du texte</h2>
    <p class="mb-4 text-muted">Agrandit tout l’écran de l’application (pas les PDF). Le choix est gardé pour votre compte.</p>

    <form method="post" action="{{ route('profile.text-size') }}">
        @csrf
        @method('patch')

        <fieldset class="grid gap-3 sm:grid-cols-3">
            <legend class="sr-only">Taille du texte</legend>
            @foreach (\App\Models\User::TEXT_SIZES as $value => $label)
                <label @class([
                    'flex cursor-pointer flex-col items-center gap-1 rounded-xl border-2 px-4 py-3 transition hover:border-brand/60',
                    'border-brand bg-brand-soft' => $user->text_size === $value,
                    'border-line bg-white' => $user->text_size !== $value,
                ])>
                    <input type="radio" name="text_size" value="{{ $value }}" class="sr-only" @checked($user->text_size === $value) onchange="this.form.submit()">
                    <span @class(['font-bold text-ink', 'text-base' => $value === 'normal', 'text-xl' => $value === 'large', 'text-2xl' => $value === 'xlarge'])>Aa</span>
                    <span class="font-semibold">{{ $label }}</span>
                </label>
            @endforeach
        </fieldset>

        <noscript><button type="submit" class="btn-primary mt-4">Enregistrer</button></noscript>
    </form>
</section>
