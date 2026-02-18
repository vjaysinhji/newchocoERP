<?php

use App\Http\Controllers\ProductController;
use Modules\Manufacturing\Http\Controllers\ProductionController;
use Modules\Manufacturing\Http\Controllers\RecipeController;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

$useTenancy = false;
try {
    $useTenancy = (bool) config('database.connections.saleprosaas_landlord');
} catch (\Throwable $e) {
    // default to non-tenancy when config not available
}

$middleware = ['common', 'auth', 'active'];
if ($useTenancy) {
    $middleware[] = InitializeTenancyByDomain::class;
    $middleware[] = PreventAccessFromCentralDomains::class;
}

Route::middleware($middleware)->group(function () {
    Route::controller(ProductionController::class)->group(function () {
        Route::prefix('manufacturing/productions')->group(function () {
            Route::post('production-data', 'productionData')->name('productions.data');
            Route::get('product_production/{id}', 'productProductionData');
        });
    });
    Route::resource('manufacturing/productions', ProductionController::class)->except(['show']);
    Route::resource('manufacturing/recipes', RecipeController::class)->except(['show']);
    Route::post('manufacturing/products/product-data', [ProductController::class, 'productData'])->name('get-products');
    Route::post('manufacturing/product-data', [RecipeController::class, 'productData'])->name('manufacturing.product-data');
    Route::get('manufacturing/recipes/lims_product_search', [ProductController::class, 'limsProductSearch'])->name('manufacturing.recipe.product.search');
    Route::get('manufacturing/recipes/lims_rawmaterial_search', [\App\Http\Controllers\RawMaterialAdjustmentController::class, 'limsRawMaterialSearch'])->name('rawmaterial.recipe.search');
    Route::post('manufacturing/get-Ingredients', [ProductionController::class, 'getIngredients'])->name('get-Ingredients');
    Route::post('products/getdata/{id}/{variant_id}', [ProductController::class, 'getData'])->name('manufacturing.products.getdata');
    Route::prefix('manufacturing')->group(function () {
        Route::get('/', 'ManufacturingController@index');
    });
});







