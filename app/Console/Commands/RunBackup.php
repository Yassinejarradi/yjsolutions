<?php

namespace App\Console\Commands;

use App\Support\Backup;
use Illuminate\Console\Command;
use Throwable;

/**
 * Sauvegarde la base et les logos dans storage/app/backups (planifiée chaque jour, voir routes/console.php).
 *
 *   php artisan backup:run
 */
class RunBackup extends Command
{
    protected $signature = 'backup:run';

    protected $description = 'Sauvegarder la base de données et les fichiers téléversés';

    public function handle(): int
    {
        try {
            $path = Backup::create();
        } catch (Throwable $e) {
            report($e);
            $this->error('La sauvegarde a échoué : '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('Sauvegarde créée : '.$path);

        return self::SUCCESS;
    }
}
