<?php

namespace App\Console\Commands;

use App\Support\Backup;
use Illuminate\Console\Command;
use Throwable;

/**
 * Sauvegarde la base et les logos dans storage/app/backups (planifiée chaque jour, voir routes/console.php).
 *
 *   php artisan backup:run
 *   php artisan backup:run --to="D:\Sauvegardes" --keep=30   (backup.bat de l'installation Windows)
 */
class RunBackup extends Command
{
    protected $signature = 'backup:run
        {--to= : Dossier des sauvegardes (sinon celui de config/backup.php)}
        {--keep= : Nombre de sauvegardes gardées dans ce dossier}';

    protected $description = 'Sauvegarder la base de données et les fichiers téléversés';

    public function handle(): int
    {
        // Réglé ici plutôt que par variable d'environnement : la configuration peut être en cache (optimize).
        if (filled($this->option('to'))) {
            config(['backup.path' => rtrim($this->option('to'), '\\/')]);
        }
        if (filled($this->option('keep'))) {
            config(['backup.keep' => max(1, (int) $this->option('keep'))]);
        }

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
