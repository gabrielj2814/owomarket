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
        Schema::create('shipping_rates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('shipping_zone_id');
            $table->foreign('shipping_zone_id')->references('id')->on('shipping_zones');
            $table->string('name');
            $table->enum('type', ['flat', 'free', 'price_based', 'weight_based']);
            $table->decimal('min_value', 10, 2)->nullable();
            $table->decimal('max_value', 10, 2)->nullable();
            $table->decimal('cost', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_rates');
    }
};
