<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Connexion – JY Solutions</title>
    <link rel="icon" href="{{ route('logo') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="flex min-h-screen flex-col items-center justify-center px-4 py-10">
        <img src="{{ route('logo') }}" alt="JY Solutions" class="mb-6 h-28 w-auto">

        <div class="w-full max-w-md rounded-xl border-t-4 border-accent bg-white p-6 shadow-md sm:p-8">
            {{ $slot }}
        </div>
    </div>
</body>
</html>
