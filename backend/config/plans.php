<?php

// Convertit une valeur d'env en entier, ou null si non définie/vide (illimité).
$nullableInt = static function ($value): ?int {
    return ($value === null || $value === '') ? null : (int) $value;
};

return [
    /*
    |--------------------------------------------------------------------------
    | Limites par taille d'entreprise (forfait)
    |--------------------------------------------------------------------------
    | Le forfait de l'entreprise correspond directement à sa taille
    | (Company.size : 'small', 'medium', 'large'). Chaque palier définit :
    |   - max_users      : nombre max d'utilisateurs actifs (null = illimité)
    |   - max_documents  : nombre max de documents actifs hors corbeille
    |                      (null = illimité)
    |   - label          : libellé lisible pour l'UI
    |
    | Surchargeable par variables d'environnement, sans toucher au code.
    */
    'tiers' => [
        'small' => [
            'label' => 'Petite entreprise',
            'max_users' => (int) env('PLAN_SMALL_MAX_USERS', 10),
            'max_documents' => (int) env('PLAN_SMALL_MAX_DOCUMENTS', 500),
        ],
        'medium' => [
            'label' => 'Moyenne entreprise',
            'max_users' => (int) env('PLAN_MEDIUM_MAX_USERS', 50),
            'max_documents' => (int) env('PLAN_MEDIUM_MAX_DOCUMENTS', 5000),
        ],
        'large' => [
            'label' => 'Grande entreprise',
            'max_users' => $nullableInt(env('PLAN_LARGE_MAX_USERS')),
            'max_documents' => $nullableInt(env('PLAN_LARGE_MAX_DOCUMENTS')),
        ],
    ],
];