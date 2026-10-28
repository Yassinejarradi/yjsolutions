<?php

use Illuminate\Support\Facades\Schedule;

// Sauvegarde quotidienne. Le planificateur doit être lancé chaque minute :
// cron « * * * * * php artisan schedule:run » (voir README, Mise en production).
Schedule::command('backup:run')->dailyAt(config('backup.time'))->withoutOverlapping();
