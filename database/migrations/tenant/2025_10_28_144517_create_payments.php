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
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table->string('order_id');
            $table->foreign('order_id')->references('id')->on('orders');
            $table->string('payment_gateway');
            $table->string('transaction_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->decimal('fee', 10, 2)->default(0);
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded', 'partially_refunded']);
            $table->string('currency')->default('USD');
            $table->json('gateway_response')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
