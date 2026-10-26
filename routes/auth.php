<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordController;
use Illuminate\Support\Facades\Route;

// Connexion uniquement : pas d'inscription publique ni de réinitialisation par email.
// Les comptes se créent dans Paramètres > Utilisateurs ou avec « php artisan user:create »,
// et un mot de passe oublié se réinitialise avec « php artisan user:password ».

Route::middleware('guest')->group(function () {
    Route::get('connexion', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('connexion', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::put('mot-de-passe', [PasswordController::class, 'update'])->name('password.update');

    Route::post('deconnexion', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
