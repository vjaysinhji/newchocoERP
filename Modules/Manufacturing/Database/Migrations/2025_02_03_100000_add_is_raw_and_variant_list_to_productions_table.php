<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIsRawAndVariantListToProductionsTable extends Migration
{
    public function up()
    {
        Schema::table('productions', function (Blueprint $table) {
            $table->string('is_raw_material_list')->nullable()->after('qty_list');
            $table->string('variant_list')->nullable()->after('is_raw_material_list');
        });
    }

    public function down()
    {
        Schema::table('productions', function (Blueprint $table) {
            $table->dropColumn(['is_raw_material_list', 'variant_list']);
        });
    }
}
