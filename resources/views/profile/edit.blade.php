<x-app-layout title="Mon profil">
    <div class="space-y-8">
        <div class="card max-w-2xl">
            @include('profile.partials.text-size-form')
        </div>

        <div class="card max-w-2xl">
            @include('profile.partials.update-profile-information-form')
        </div>

        <div class="card max-w-2xl">
            @include('profile.partials.update-password-form')
        </div>

        <div class="card max-w-2xl">
            <h2 class="card-title">Installer l’application sur le téléphone</h2>
            <p class="mb-4 text-muted">Une icône « JY Solutions » s’ajoute à l’écran d’accueil et ouvre l’application en plein écran, comme une application normale.</p>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <p class="font-semibold">Android (Chrome)</p>
                    <ol class="mt-1 list-decimal space-y-1 pl-5 text-muted">
                        <li>Ouvrir l’application dans Chrome.</li>
                        <li>Toucher le menu <b>⋮</b> en haut à droite.</li>
                        <li>Choisir « Ajouter à l’écran d’accueil » ou « Installer l’application ».</li>
                    </ol>
                </div>
                <div>
                    <p class="font-semibold">iPhone (Safari)</p>
                    <ol class="mt-1 list-decimal space-y-1 pl-5 text-muted">
                        <li>Ouvrir l’application dans Safari.</li>
                        <li>Toucher le bouton Partager (carré avec une flèche).</li>
                        <li>Choisir « Sur l’écran d’accueil ».</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
