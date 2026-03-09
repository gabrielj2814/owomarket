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
        Schema::create('order_items', function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table->string('order_id');
            $table->foreign('order_id')->references('id')->on('orders');
            $table->string('product_id');
            $table->foreign('product_id')->references('id')->on('products');
            $table->string('product_variant_id')->nullable();
            $table->foreign('product_variant_id')->references('id')->on('product_variants')->onDelete('set null');
            $table->string('product_name');
            $table->string('sku');
            $table->decimal('price', 10, 2);
            $table->integer('quantity');
            $table->json('attributes')->nullable();
            $table->decimal('total', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
