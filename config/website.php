<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Website Default Locale (fallback - admin can override in General Settings)
    |--------------------------------------------------------------------------
    */
    'default_locale' => env('WEBSITE_DEFAULT_LOCALE', 'en'),

    /*
    |--------------------------------------------------------------------------
    | Supported Locales
    |--------------------------------------------------------------------------
    */
    'supported_locales' => [
        'en' => ['name' => 'English', 'rtl' => false],
        'hi' => ['name' => 'हिन्दी', 'rtl' => false],
        'ur' => ['name' => 'اردو', 'rtl' => false],
        'ar' => ['name' => 'العربية', 'rtl' => true],
    ],

    /*
    |--------------------------------------------------------------------------
    | RTL Locales (fallback - admin can override in General Settings)
    |--------------------------------------------------------------------------
    */
    'rtl_locales' => ['ar', 'ur'],
];
