<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sale_addresses', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('sale_id')->unsigned();
            $table->integer('customer_id')->unsigned();
            $table->string('invoice_number')->unique();
            $table->string('address')->nullable();
            $table->string('area')->nullable();
            $table->string('house_number')->nullable();
            $table->string('street')->nullable();
            $table->string('ave')->nullable();
            $table->string('block')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_addresses');
    }
};

