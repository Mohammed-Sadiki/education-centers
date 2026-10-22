<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Source locale
    |--------------------------------------------------------------------------
    |
    | Dynamic content (formations, announcements, centers...) is authored in
    | French. This locale is never translated: the original column is used.
    |
    */

    'source_locale' => env('TRANSLATION_SOURCE_LOCALE', 'fr'),

    /*
    |--------------------------------------------------------------------------
    | Supported target locales
    |--------------------------------------------------------------------------
    */

    'target_locales' => ['ar'],

    /*
    |--------------------------------------------------------------------------
    | Automatic translation
    |--------------------------------------------------------------------------
    |
    | When no translation exists, it is fetched from the configured provider
    | lazily at display time and then cached. Private messages (Message model)
    | never use this mechanism.
    |
    */

    'auto_translate' => (bool) env('TRANSLATION_AUTO', true),

    /*
    |--------------------------------------------------------------------------
    | Provider
    |--------------------------------------------------------------------------
    |
    | "google" uses the Google Cloud Translation API (v2), "deepl" uses the
    | DeepL API. Without credentials the null provider is used and the
    | original French content is displayed instead.
    |
    */

    'provider' => env('TRANSLATION_PROVIDER', 'google'),

    'providers' => [
        'google' => [
            'key' => env('GOOGLE_TRANSLATE_API_KEY'),
            'endpoint' => env('GOOGLE_TRANSLATE_ENDPOINT', 'https://translation.googleapis.com/language/translate/v2'),
            'timeout' => (int) env('TRANSLATION_TIMEOUT', 8),
        ],
        'deepl' => [
            'key' => env('DEEPL_API_KEY'),
            'endpoint' => env('DEEPL_ENDPOINT', 'https://api-free.deepl.com/v2/translate'),
            'timeout' => (int) env('TRANSLATION_TIMEOUT', 8),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | Translated values are cached to avoid querying the database on every
    | page render. Cache entries are invalidated when content is updated.
    |
    */

    'cache_prefix' => 'content-translation',

    'cache_ttl' => (int) env('TRANSLATION_CACHE_TTL', 86400),

];
