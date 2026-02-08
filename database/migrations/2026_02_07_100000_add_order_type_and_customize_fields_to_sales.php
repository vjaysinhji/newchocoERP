<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddOrderTypeAndCustomizeFieldsToSales extends Migration
{
    /**
     * Run the migrations.
     * order_type: 1 = Display (default), 2 = Customization (Box/Tray)
     * customize_type_id on product_sales: 37 = BOXES, 38 = EMPTY TRAY, custome = Customer Tray
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('sales', 'order_type')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->tinyInteger('order_type')->default(1)->after('sale_status')->comment('1=Display, 2=Customization');
            });
        }

        if (!Schema::hasColumn('product_sales', 'customize_type_id')) {
            Schema::table('product_sales', function (Blueprint $table) {
                $table->string('customize_type_id', 20)->nullable()->after('total')->comment('37=BOXES, 38=EMPTY TRAY, custome=Customer Tray');
            });
        }

        if (!Schema::hasColumn('product_sales', 'custom_sort')) {
            Schema::table('product_sales', function (Blueprint $table) {
                $table->unsignedInteger('custom_sort')->nullable()->after('customize_type_id')->comment('Sort order under same customize type');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('sales', 'order_type')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->dropColumn('order_type');
            });
        }
        if (Schema::hasColumn('product_sales', 'customize_type_id')) {
            Schema::table('product_sales', function (Blueprint $table) {
                $table->dropColumn('customize_type_id');
            });
        }
        if (Schema::hasColumn('product_sales', 'custom_sort')) {
            Schema::table('product_sales', function (Blueprint $table) {
                $table->dropColumn('custom_sort');
            });
        }
    }
}
