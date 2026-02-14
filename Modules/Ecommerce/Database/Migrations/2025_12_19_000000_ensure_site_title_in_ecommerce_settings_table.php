<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class EnsureSiteTitleInEcommerceSettingsTable extends Migration
{
    /**
     * Run the migrations.
     * Ensures site_title column exists and backfills missing values.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('ecommerce_settings')) {
            if (!Schema::hasColumn('ecommerce_settings', 'site_title')) {
                Schema::table('ecommerce_settings', function (Blueprint $table) {
                    $table->string('site_title')->nullable()->after('id');
                });
            }
            DB::table('ecommerce_settings')->whereNull('site_title')->update(['site_title' => 'Ecommerce']);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Optional: do not drop column to avoid data loss
    }
}
