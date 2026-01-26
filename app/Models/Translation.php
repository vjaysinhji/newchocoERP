<?php

namespace App\Models;


use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Translation extends Model
{
    use HasFactory;
    
    protected $fillable = ['locale', 'group', 'key', 'value'];

    // Get Default Language (Cached)
    public static function getTrnaslactionsByLocale(string $locale)
    {
        return Cache::rememberForever("translations_by_locale_{$locale}", function () use($locale) {
            return self::where('locale', $locale)
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->group . '.' . $item->key => $item->value];
                })
                ->toArray();
        });
    }

    public static function forgetCachedTranslations($locale = null)
    {
        if ($locale) {
            Cache::forget("translations_by_locale_{$locale}");
            Cache::forget("translations_{$locale}");
        } else {
            // Clear all locale caches - get all locales from database
            $locales = self::distinct()->pluck('locale');
            foreach ($locales as $loc) {
                Cache::forget("translations_by_locale_{$loc}");
                Cache::forget("translations_{$loc}");
            }
        }
        Cache::forget('languages_list');
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'locale');
    }
}
