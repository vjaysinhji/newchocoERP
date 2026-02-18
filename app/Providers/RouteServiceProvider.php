<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{

    public const HOME = '/home';
    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    // protected $namespace = 'App\Http\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        //

        parent::boot();
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        $this->mapApiRoutes();

        // Admin routes FIRST - so /rawmaterials, /purchases, /brand, etc. work correctly
        $this->mapWebRoutes();

        // Manufacturing module routes - before Ecommerce so /manufacturing/* is not caught by catch-all
        $this->mapManufacturingRoutes();

        // Ecommerce frontend routes SECOND - /, /shop, /brands, /collections, etc.
        $this->mapEcommerceRoutes();
    }

    /**
     * Manufacturing module web routes (productions, recipes, etc.)
     */
    protected function mapManufacturingRoutes()
    {
        $path = base_path('Modules/Manufacturing/Routes/web.php');
        if (!file_exists($path)) {
            return;
        }
        Route::middleware('web')
            ->namespace('Modules\Manufacturing\Http\Controllers')
            ->group($path);
    }

    /**
     * Ecommerce frontend (store) routes - loaded after admin so admin gets priority.
     */
    protected function mapEcommerceRoutes()
    {
        $path = base_path('Modules/Ecommerce/Routes/web.php');
        if (file_exists($path)) {
            Route::middleware('web')
                ->namespace('Modules\Ecommerce\Http\Controllers')
                ->group($path);
        }
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        Route::middleware('web')
             ->namespace($this->namespace)
             ->group(base_path('routes/web.php'));
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        Route::prefix('api')
             ->middleware('api')
             ->namespace($this->namespace)
             ->group(base_path('routes/api.php'));
    }
}
