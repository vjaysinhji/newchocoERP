<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddWarehouseStoreProductIdToProductSalesTable extends Migration
{
    /**
     * Run the migrations.
     * When set, this row is a warehouse store (basement) item from POS customize; product_id may be null.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('product_sales', function (Blueprint $table) {
            $table->unsignedInteger('warehouse_store_product_id')->nullable()->after('product_id');
            $table->unsignedInteger('product_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('product_sales', function (Blueprint $table) {
            $table->dropColumn('warehouse_store_product_id');
            $table->unsignedInteger('product_id')->nullable(false)->change();
        });
    }
}
