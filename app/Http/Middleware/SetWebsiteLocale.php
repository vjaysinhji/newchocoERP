<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetWebsiteLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->route('locale');

        if ($locale && array_key_exists($locale, config('website.supported_locales', []))) {
            App::setLocale($locale);
        } else {
            App::setLocale(config('website.default_locale', 'en'));
        }

        return $next($request);
    }
}
