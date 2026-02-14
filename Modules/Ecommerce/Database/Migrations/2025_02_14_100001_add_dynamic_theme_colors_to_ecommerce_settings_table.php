<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDynamicThemeColorsToEcommerceSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('ecommerce_settings')) {
            Schema::table('ecommerce_settings', function (Blueprint $table) {
                if (!Schema::hasColumn('ecommerce_settings', 'header_bg_color')) {
                    $table->string('header_bg_color')->nullable()->after('theme_color');
                }
                if (!Schema::hasColumn('ecommerce_settings', 'cta_bg_color')) {
                    $table->string('cta_bg_color')->nullable()->after('header_bg_color');
                }
                if (!Schema::hasColumn('ecommerce_settings', 'featured_collection_id')) {
                    $table->unsignedBigInteger('featured_collection_id')->nullable()->after('cta_bg_color');
                }
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
        if (Schema::hasTable('ecommerce_settings')) {
            Schema::table('ecommerce_settings', function (Blueprint $table) {
                $table->dropColumn(['header_bg_color', 'cta_bg_color', 'featured_collection_id']);
            });
        }
    }
}
