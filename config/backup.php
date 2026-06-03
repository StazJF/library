<?php

return [
    'password' => env('BACKUP_PASSWORD'),
    'require_password' => (bool) env('BACKUP_REQUIRE_PASSWORD', true),
    // Options: auto, aes256, zipcrypto
    // Note: Windows File Explorer often cannot extract encrypted ZIPs; 7-Zip is recommended.
    'zip_encryption' => env('BACKUP_ZIP_ENCRYPTION', 'auto'),

    // When set (or when ProgramData exists), the app will also keep an overwritten copy of the latest
    // ZIP at this location. This is a server-side copy (not the browser's Downloads folder).
    'secure_export_dir' => env('BACKUP_SECURE_EXPORT_DIR')
        ?: (getenv('ProgramData') ? (getenv('ProgramData') . DIRECTORY_SEPARATOR . 'LibraryBackups') : null),
];
