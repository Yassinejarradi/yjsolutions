<section>
    <h2 class="card-title">Changer mon mot de passe</h2>

    <form method="post" action="{{ route('password.update') }}" class="space-y-5">
        @csrf
        @method('put')

        @foreach ([
            ['current_password', 'Mot de passe actuel', 'current-password'],
            ['password', 'Nouveau mot de passe', 'new-password'],
            ['password_confirmation', 'Confirmer le nouveau mot de passe', 'new-password'],
        ] as [$name, $label, $autocomplete])
            <div>
                <label for="update_{{ $name }}" class="field-label">{{ $label }}</label>
                <input id="update_{{ $name }}" name="{{ $name }}" type="password" class="field-input" autocomplete="{{ $autocomplete }}">
                @foreach ($errors->updatePassword->get($name) as $message)
                    <p class="field-error">{{ $message }}</p>
                @endforeach
            </div>
        @endforeach

        <button type="submit" class="btn-primary">Changer le mot de passe</button>
    </form>
</section>
