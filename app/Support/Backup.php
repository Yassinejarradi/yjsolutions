<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;
use ZipArchive;

/**
 * Sauvegarde complète dans un seul fichier .zip :
 * - la base (copie du fichier SQLite, ou export SQL pour MySQL, sans besoin de mysqldump) ;
 * - les fichiers téléversés (logos) de storage/app/private, et storage/app/public ;
 * - un LISEZMOI.txt qui explique la restauration.
 */
class Backup
{
    /** Nom d'un fichier de sauvegarde (le seul format accepté au téléchargement). */
    public const NAME_PATTERN = '/^sauvegarde-\d{4}-\d{2}-\d{2}-\d{6}\.zip$/';

    /** Tables techniques dont le contenu n'a pas besoin d'être gardé (structure seulement). */
    private const SKIP_DATA = ['sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs'];

    public static function directory(): string
    {
        return config('backup.path');
    }

    /**
     * Crée une sauvegarde et supprime les plus anciennes au-delà de backup.keep.
     *
     * @return string chemin du fichier créé
     */
    public static function create(): string
    {
        File::ensureDirectoryExists(self::directory());

        $name = 'sauvegarde-'.now()->format('Y-m-d-His').'.zip';
        $zipPath = self::directory().DIRECTORY_SEPARATOR.$name;
        $temp = self::directory().DIRECTORY_SEPARATOR.'.en-cours-'.uniqid();

        try {
            $zip = new ZipArchive;
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Impossible de créer le fichier '.$zipPath);
            }

            $databaseFile = self::dumpDatabase($temp);
            $zip->addFile($databaseFile, basename($databaseFile));

            self::addFolder($zip, storage_path('app/private'), 'fichiers/');
            self::addFolder($zip, storage_path('app/public'), 'public/');

            $zip->addFromString('LISEZMOI.txt', self::readme(basename($databaseFile)));
            $zip->close();
        } finally {
            File::deleteDirectory($temp);
        }

        self::prune();

        return $zipPath;
    }

    /** Ajoute un dossier au zip, sans les envois en cours de Livewire (temporaires, inutiles à restaurer). */
    private static function addFolder(ZipArchive $zip, string $folder, string $prefix): void
    {
        if (! File::isDirectory($folder)) {
            return;
        }

        foreach (File::allFiles($folder) as $file) {
            $relative = str_replace('\\', '/', $file->getRelativePathname());
            if (! str_starts_with($relative, 'livewire-tmp/')) {
                $zip->addFile($file->getPathname(), $prefix.$relative);
            }
        }
    }

    /**
     * Sauvegardes existantes, de la plus récente à la plus ancienne.
     *
     * @return list<array{name: string, size: int, date: Carbon}>
     */
    public static function all(): array
    {
        if (! File::isDirectory(self::directory())) {
            return [];
        }

        return collect(File::files(self::directory()))
            ->filter(fn ($file) => preg_match(self::NAME_PATTERN, $file->getFilename()))
            ->map(fn ($file) => [
                'name' => $file->getFilename(),
                'size' => $file->getSize(),
                'date' => Carbon::createFromFormat('Y-m-d-His', substr($file->getFilename(), 11, 17)),
            ])
            ->sortByDesc('name')
            ->values()
            ->all();
    }

    /** Chemin d'une sauvegarde existante, ou null (nom invalide ou fichier absent). */
    public static function path(string $name): ?string
    {
        $path = self::directory().DIRECTORY_SEPARATOR.$name;

        return preg_match(self::NAME_PATTERN, $name) && File::isFile($path) ? $path : null;
    }

    private static function prune(): void
    {
        collect(self::all())
            ->slice(max(1, (int) config('backup.keep')))
            ->each(fn (array $backup) => File::delete(self::directory().DIRECTORY_SEPARATOR.$backup['name']));
    }

    /** Écrit la base dans le dossier temporaire et renvoie le chemin du fichier. */
    private static function dumpDatabase(string $temp): string
    {
        File::ensureDirectoryExists($temp);
        $driver = DB::connection()->getDriverName();

        return match ($driver) {
            'sqlite' => self::dumpSqlite($temp.DIRECTORY_SEPARATOR.'database.sqlite'),
            'mysql', 'mariadb' => self::dumpMysql($temp.DIRECTORY_SEPARATOR.'database.sql'),
            default => throw new RuntimeException("Sauvegarde non prévue pour la base « {$driver} »."),
        };
    }

    /** Copie cohérente de la base SQLite, même si elle est utilisée au même moment. */
    private static function dumpSqlite(string $target): string
    {
        DB::statement('VACUUM INTO ?', [$target]);

        return $target;
    }

    /** Export SQL (structure + données) écrit au fil de l'eau, table par table. */
    private static function dumpMysql(string $target): string
    {
        $pdo = DB::connection()->getPdo();
        $out = fopen($target, 'w');

        fwrite($out, "-- Sauvegarde JY Solutions Gestion du ".now()->format('d/m/Y H:i')."\n");
        fwrite($out, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");

        foreach (DB::select('SHOW TABLES') as $row) {
            $table = array_values((array) $row)[0];
            $create = array_values((array) DB::selectOne("SHOW CREATE TABLE `{$table}`"))[1];
            fwrite($out, "DROP TABLE IF EXISTS `{$table}`;\n{$create};\n");

            if (! in_array($table, self::SKIP_DATA, true)) {
                foreach (DB::table($table)->cursor() as $record) {
                    $values = array_map(fn ($value) => $value === null ? 'NULL' : $pdo->quote((string) $value), (array) $record);
                    $columns = implode('`, `', array_keys((array) $record));
                    fwrite($out, "INSERT INTO `{$table}` (`{$columns}`) VALUES (".implode(', ', $values).");\n");
                }
            }

            fwrite($out, "\n");
        }

        fwrite($out, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($out);

        return $target;
    }

    private static function readme(string $databaseFile): string
    {
        $restore = $databaseFile === 'database.sqlite'
            ? "Remplacer database/database.sqlite de l'application par le fichier database.sqlite de cette sauvegarde."
            : "Importer database.sql dans la base MySQL : mysql -u UTILISATEUR -p NOM_BASE < database.sql";

        return "Sauvegarde JY Solutions Gestion du ".now()->format('d/m/Y à H:i')."\r\n\r\n"
            ."Pour restaurer :\r\n"
            ."1. Arrêter l'application.\r\n"
            ."2. {$restore}\r\n"
            ."3. Copier le contenu du dossier « fichiers » dans storage/app/private (logos),\r\n"
            ."   et celui du dossier « public » (s'il existe) dans storage/app/public.\r\n"
            ."4. Relancer l'application.\r\n";
    }
}
