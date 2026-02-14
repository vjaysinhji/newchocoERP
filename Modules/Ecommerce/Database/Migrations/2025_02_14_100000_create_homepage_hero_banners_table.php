<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHomepageHeroBannersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('homepage_hero_banners', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('title_ar')->nullable();
            $table->text('subtitle')->nullable();
            $table->text('subtitle_ar')->nullable();
            $table->string('cta_text')->nullable();
            $table->string('cta_text_ar')->nullable();
            $table->string('cta_link')->nullable();
            $table->string('image')->nullable();
            $table->string('bg_color')->default('#8B1538');
            $table->string('text_color')->default('#FFFFFF');
            $table->integer('order')->default(0);
            $table->integer('status')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('homepage_hero_banners');
    }
}
