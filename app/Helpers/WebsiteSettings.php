<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class WebsiteSettings
{
    protected static ?object $settings = null;

    /**
     * Get website settings from DB (cached). Admin locale is separate.
     */
    public static function get(): object
    {
        if (self::$settings !== null) {
            return self::$settings;
        }

        self::$settings = Cache::remember('website_settings', 60 * 60 * 24, function () {
            try {
                $row = DB::table('general_settings')->latest()->first();
                return (object) [
                    'default_locale' => $row->website_default_locale ?? config('website.default_locale', 'en'),
                    'rtl_locales' => $row->website_rtl_locales
                        ? explode(',', $row->website_rtl_locales)
                        : config('website.rtl_locales', ['ar', 'ur']),
                ];
            } catch (\Throwable $e) {
                return (object) [
                    'default_locale' => config('website.default_locale', 'en'),
                    'rtl_locales' => config('website.rtl_locales', ['ar', 'ur']),
                ];
            }
        });

        return self::$settings;
    }

    public static function defaultLocale(): string
    {
        return self::get()->default_locale;
    }

    public static function rtlLocales(): array
    {
        return self::get()->rtl_locales;
    }

    public static function isRtl(string $locale): bool
    {
        return in_array($locale, self::rtlLocales(), true);
    }
}
