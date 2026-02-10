<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            $table->string('website_default_locale', 10)->nullable()->after('is_rtl');
            $table->string('website_rtl_locales', 100)->nullable()->after('website_default_locale');
        });
    }

    public function down(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            $table->dropColumn(['website_default_locale', 'website_rtl_locales']);
        });
    }
};
