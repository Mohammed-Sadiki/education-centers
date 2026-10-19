<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Devise principale de la plateforme
    |--------------------------------------------------------------------------
    |
    | Toute la plateforme Education Centers utilise le Dirham Marocain (MAD)
    | comme devise principale. L'affichage utilisateur utilise « DH ».
    |
    */

    'default' => env('APP_CURRENCY', 'MAD'),

    'symbol'  => env('APP_CURRENCY_SYMBOL', 'DH'),

    /*
    | Devises acceptées dans les formulaires
    */
    'allowed' => ['MAD', 'EUR', 'USD'],
];
