<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * POS-specific sequential invoice numbering (1, 2, 3... 999, 1000, 1001)
     * with concurrency-safe atomic increment.
     */
    public function up(): void
    {
        Schema::create('pos_invoice_sequences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('last_invoice_number')->default(0);
            $table->timestamps();
        });

        // Seed initial row
        DB::table('pos_invoice_sequences')->insert([
            'last_invoice_number' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_invoice_sequences');
    }
};
