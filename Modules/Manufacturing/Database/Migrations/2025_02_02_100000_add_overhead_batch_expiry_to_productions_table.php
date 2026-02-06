<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddOverheadBatchExpiryToProductionsTable extends Migration
{
    public function up()
    {
        Schema::table('productions', function (Blueprint $table) {
            $table->string('production_overhead_type')->nullable()->after('production_cost');
            $table->decimal('production_overhead_cost', 15, 2)->default(0)->nullable()->after('production_overhead_type');
            $table->string('batch_lot_number')->nullable()->after('reference_no');
            $table->date('expiry_date')->nullable()->after('batch_lot_number');
        });
    }

    public function down()
    {
        Schema::table('productions', function (Blueprint $table) {
            $table->dropColumn(['production_overhead_type', 'production_overhead_cost', 'batch_lot_number', 'expiry_date']);
        });
    }
}
