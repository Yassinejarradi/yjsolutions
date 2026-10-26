<x-app-layout title="Mon profil">
    <div class="space-y-8">
        <div class="card max-w-2xl">
            @include('profile.partials.update-profile-information-form')
        </div>

        <div class="card max-w-2xl">
            @include('profile.partials.update-password-form')
        </div>
    </div>
</x-app-layout>
