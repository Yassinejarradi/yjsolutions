<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\Settings\CompanySettingsController;
use App\Http\Controllers\Settings\DocumentSettingsController;
use App\Http\Controllers\Settings\LogoController;
use App\Http\Controllers\Settings\UserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/tableau-de-bord');

// Logo de la société (aussi affiché sur la page de connexion).
Route::get('logo', LogoController::class)->name('logo');

Route::middleware('auth')->group(function () {
    Route::get('tableau-de-bord', DashboardController::class)->name('dashboard');

    Route::resource('clients', ClientController::class);
    Route::patch('clients/{client}/archiver', [ClientController::class, 'archive'])->name('clients.archive');
    Route::patch('clients/{client}/reactiver', [ClientController::class, 'restore'])->name('clients.restore');

    // Devis : /devis, /devis/nouveau, /devis/12 (éditeur si brouillon, lecture sinon)
    Route::resource('devis', QuoteController::class)
        ->only(['index', 'create', 'show', 'destroy'])
        ->parameters(['devis' => 'quote'])
        ->names('quotes');
    Route::prefix('devis/{quote}')->name('quotes.')->controller(QuoteController::class)->group(function () {
        Route::get('pdf', 'pdf')->name('pdf');
        Route::get('word', 'word')->name('word');
        Route::post('dupliquer', 'duplicate')->name('duplicate');
        Route::patch('accepter', 'accept')->name('accept');
        Route::patch('refuser', 'refuse')->name('refuse');
    });

    Route::get('profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profil', [ProfileController::class, 'update'])->name('profile.update');

    Route::prefix('parametres')->name('settings.')->group(function () {
        Route::redirect('/', '/parametres/societe')->name('index');

        Route::get('societe', [CompanySettingsController::class, 'edit'])->name('company');
        Route::put('societe', [CompanySettingsController::class, 'update'])->name('company.update');
        Route::delete('societe/logo', [CompanySettingsController::class, 'destroyLogo'])->name('company.logo.destroy');

        Route::get('documents', [DocumentSettingsController::class, 'edit'])->name('documents');
        Route::put('documents', [DocumentSettingsController::class, 'update'])->name('documents.update');

        Route::resource('utilisateurs', UserController::class)
            ->except('show')
            ->parameters(['utilisateurs' => 'user'])
            ->names('users');
    });
});

require __DIR__.'/auth.php';
