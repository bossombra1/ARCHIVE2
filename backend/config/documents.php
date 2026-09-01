<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Disque de stockage des documents
    |--------------------------------------------------------------------------
    | Par défaut : 'local' = storage/app/private (non accessible publiquement).
    */
    'disk' => env('DOCUMENTS_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Taille maximale d'un fichier (en kilo-octets)
    |--------------------------------------------------------------------------
    | 10 Mo par défaut. Doit rester cohérent avec upload_max_filesize / post_max_size
    * côté PHP.
    */
    'max_size_kb' => (int) env('DOCUMENTS_MAX_SIZE_KB', 10240),

    /*
    |--------------------------------------------------------------------------
    | Extensions autorisées (validées côté serveur)
    |--------------------------------------------------------------------------
    | La validation utilise à la fois l'extension déclarée et le type MIME
    * détecté par finfo. Les valeurs doivent être cohérentes avec l'enum
    * `file_type` de la table documents.
    */
    'allowed_extensions' => ['pdf', 'png', 'jpg', 'jpeg', 'gif'],

    /*
    |--------------------------------------------------------------------------
    | Types MIME autorisés (mapping MIME -> extension)
    |--------------------------------------------------------------------------
    */
    'allowed_mimes' => [
        'application/pdf' => 'pdf',
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/gif' => 'gif',
    ],
];
