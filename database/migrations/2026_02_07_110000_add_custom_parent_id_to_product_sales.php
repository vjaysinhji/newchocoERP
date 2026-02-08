<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCustomParentIdToProductSales extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('product_sales', 'custom_parent_id')) {
            Schema::table('product_sales', function (Blueprint $table) {
                $table->unsignedInteger('custom_parent_id')->nullable()->after('custom_sort')->comment('product_sales.id of parent row in customization');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('product_sales', 'custom_parent_id')) {
            Schema::table('product_sales', function (Blueprint $table) {
                $table->dropColumn('custom_parent_id');
            });
        }
    }
}
