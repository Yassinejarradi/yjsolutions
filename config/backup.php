<?php

return [

    // Dossier des sauvegardes (en dehors de storage/app/private, qui est lui-même sauvegardé).
    'path' => env('BACKUP_PATH', storage_path('app/backups')),

    // Nombre de sauvegardes gardées : les plus anciennes sont supprimées.
    'keep' => (int) env('BACKUP_KEEP', 30),

    // Heure de la sauvegarde automatique (planificateur Laravel).
    'time' => env('BACKUP_TIME', '13:00'),

];
