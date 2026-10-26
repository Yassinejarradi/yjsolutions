<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Adresses en français : /clients/nouveau, /clients/12/modifier…
        Route::resourceVerbs(['create' => 'nouveau', 'edit' => 'modifier']);

        // Dates en français (noms des jours et des mois).
        Carbon::setLocale('fr');
    }
}
