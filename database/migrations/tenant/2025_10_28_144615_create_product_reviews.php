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
        Schema::create('product_reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('product_id');
            $table->foreign('product_id')->references('id')->on('products');
            $table->string('customer_id');
            $table->foreign('customer_id')->references('id')->on('customers');
            $table->string('order_id')->nullable();
            $table->foreign('order_id')->references('id')->on('orders');
            $table->integer('rating');
            $table->string('title')->nullable();
            $table->text('comment')->nullable();
            $table->text('response')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->timestamps();

            $table->unique(['product_id', 'customer_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_reviews');
    }
};
