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
        Schema::table('sales', function (Blueprint $table) {
            $table->string('delivery_type')->nullable()->after('staff_note');
            $table->string('order_mode')->nullable()->after('delivery_type');
            $table->date('delivery_date')->nullable()->after('order_mode');
            $table->string('delivery_time')->nullable()->after('delivery_date');
            $table->string('delivery_time2')->nullable()->after('delivery_time');
            $table->string('receiver_name')->nullable()->after('delivery_time2');
            $table->string('receiver_number')->nullable()->after('receiver_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['delivery_type', 'order_mode', 'delivery_date', 'delivery_time', 'delivery_time2', 'receiver_name', 'receiver_number']);
        });
    }
};
