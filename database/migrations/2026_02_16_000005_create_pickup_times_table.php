<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePickupTimesTable extends Migration
{
    public function up()
    {
        Schema::create('pickup_times', function (Blueprint $table) {
            $table->id();
            $table->string('from_time');
            $table->string('to_time');
            $table->integer('sort_order')->nullable();
            $table->string('type_name')->default('both');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('pickup_times');
    }
}

