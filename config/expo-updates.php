<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Expo Updates Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure the settings for the Expo Updates package.
    |
    */

    'route_prefix' => env('EXPO_UPDATES_ROUTE_PREFIX', 'updates'),

    /*
    |--------------------------------------------------------------------------
    | Default Project
    |--------------------------------------------------------------------------
    |
    | The default project to use when no project is specified in the URL or headers.
    | This should be the slug of an existing project.
    |
    */
    'default_project' => env('EXPO_UPDATES_DEFAULT_PROJECT', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Code Signing
    |--------------------------------------------------------------------------
    |
    | Configure code signing settings for manifest and directive verification.
    |
    */
    'code_signing' => [
        'enabled' => env('EXPO_UPDATES_CODE_SIGNING_ENABLED', false),
        'certificate_path' => env('EXPO_UPDATES_CERTIFICATE_PATH'),
        'private_key_path' => env('EXPO_UPDATES_PRIVATE_KEY_PATH', storage_path('app/expo-updates/private.key')),
        'cache' => [
            'enabled' => env('EXPO_UPDATES_SIGNATURE_CACHE_ENABLED', true),
            'ttl' => env('EXPO_UPDATES_SIGNATURE_CACHE_TTL', 3600),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Asset Storage
    |--------------------------------------------------------------------------
    |
    | Configure how assets are stored and served.
    |
    */
    'assets' => [
        'disk' => env('EXPO_UPDATES_ASSETS_DISK', 'local'),
        'path' => env('EXPO_UPDATES_ASSETS_PATH', 'expo-updates/assets'),
        'url' => env('EXPO_UPDATES_ASSETS_URL', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Configure caching behavior for manifests and assets.
    |
    */
    'cache' => [
        'enabled' => env('EXPO_UPDATES_CACHE_ENABLED', true),
        'ttl' => env('EXPO_UPDATES_CACHE_TTL', 3600),
    ],

    /*
    |--------------------------------------------------------------------------
    | Server Headers
    |--------------------------------------------------------------------------
    |
    | Default server-defined headers to include in responses.
    | These can be overridden per project in the database.
    |
    */
    'server_headers' => [
        'Cache-Control' => 'public, max-age=3600',
    ],

    /*
    |--------------------------------------------------------------------------
    | Asset Headers
    |--------------------------------------------------------------------------
    |
    | Default asset-specific headers to include in responses.
    | These can be overridden per project in the database.
    |
    */
    'asset_headers' => [
        'Cache-Control' => 'public, max-age=86400',
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Bindings
    |--------------------------------------------------------------------------
    |
    | Configure which model implementations to use.
    |
    */
    'models' => [
        'project' => LaravelExpoUpdates\Models\Project::class,
        'manifest' => LaravelExpoUpdates\Models\Manifest::class,
        'asset' => LaravelExpoUpdates\Models\Asset::class,
        'update_stat' => LaravelExpoUpdates\Models\UpdateStat::class,
    ],
];
