<?php

/*
|--------------------------------------------------------------------------
| Website Routes (Public - Lightweight)
|--------------------------------------------------------------------------
*/

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebsiteController;
use App\Helpers\WebsiteSettings;

$supportedLocales = config('website.supported_locales');
$supportedLocales = is_array($supportedLocales) ? $supportedLocales : ['en' => ['name' => 'English', 'rtl' => false]];
$localePattern = implode('|', array_keys($supportedLocales));

Route::get('/', fn () => redirect()->route('website.home', ['locale' => WebsiteSettings::defaultLocale()]))->name('website.root');

Route::middleware(['website.locale'])->prefix('{locale}')->where(['locale' => $localePattern])->group(function () {
    Route::get('/', [WebsiteController::class, 'home'])->name('website.home');
    Route::get('/about', [WebsiteController::class, 'about'])->name('website.about');
    Route::get('/contact', [WebsiteController::class, 'contact'])->name('website.contact');
});
