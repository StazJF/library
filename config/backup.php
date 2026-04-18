<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Backup Export Configuration
    |--------------------------------------------------------------------------
    |
    | Configure automatic backup exporting to external locations
    |
    */

    'export_path' => env('BACKUP_EXPORT_PATH', ''),

    /*
    |--------------------------------------------------------------------------
    | Backup Retention
    |--------------------------------------------------------------------------
    |
    | Number of days to retain old backup files (0 to disable cleanup)
    |
    */

    'retention_days' => env('BACKUP_RETENTION_DAYS', 30),
];
