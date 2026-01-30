<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('basement_purchases', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('purchase_id');
            $table->integer('basement_id');
            $table->double('qty');
            $table->double('recieved');
            $table->integer('purchase_unit_id');
            $table->double('net_unit_cost');
            $table->double('net_unit_margin')->nullable();
            $table->string('net_unit_margin_type', 20)->default('percentage');
            $table->double('net_unit_price')->nullable();
            $table->double('discount');
            $table->double('tax_rate');
            $table->double('tax');
            $table->double('total');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('basement_purchases');
    }
};
