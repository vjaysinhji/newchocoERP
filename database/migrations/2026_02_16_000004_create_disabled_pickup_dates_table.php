<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDisabledPickupDatesTable extends Migration
{
    public function up()
    {
        Schema::create('disabled_pickup_dates', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('reason_en')->nullable();
            $table->string('reason_ar')->nullable();
            $table->integer('sort_order')->nullable();
            $table->string('type_name')->default('both');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('disabled_pickup_dates');
    }
}

