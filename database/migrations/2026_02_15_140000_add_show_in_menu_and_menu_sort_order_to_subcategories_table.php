<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddShowInMenuAndMenuSortOrderToSubcategoriesTable extends Migration
{
    /**
     * Run the migrations.
     * Same field names as categories table for navbar: show_in_menu, menu_sort_order.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('subcategories', function (Blueprint $table) {
            $table->boolean('show_in_menu')->default(false)->after('slug');
            $table->unsignedInteger('menu_sort_order')->nullable()->after('show_in_menu');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('subcategories', function (Blueprint $table) {
            $table->dropColumn(['show_in_menu', 'menu_sort_order']);
        });
    }
}
