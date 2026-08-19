<?php

declare(strict_types=1);

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
        Schema::create('commission_settlements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('settlement_number')->unique();
            $table->string('tenant_id');
            $table->enum('type', ['collection', 'payout'])->default('collection');
            $table->integer('total_orders_count')->default(0);
            $table->decimal('gross_sales_amount', 10, 2)->default(0.00);
            $table->decimal('commission_amount', 10, 2)->default(0.00);
            $table->decimal('net_amount', 10, 2)->default(0.00);
            $table->string('currency')->default('USD');
            $table->enum('status', ['pending', 'settled', 'cancelled'])->default('pending');
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            $table->timestamp('settled_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index(['tenant_id', 'status']);
        });

        if (Schema::hasTable('platform_commissions') && ! Schema::hasColumn('platform_commissions', 'settlement_id')) {
            Schema::table('platform_commissions', function (Blueprint $table) {
                $table->uuid('settlement_id')->nullable()->after('status');
                $table->foreign('settlement_id')->references('id')->on('commission_settlements')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('platform_commissions') && Schema::hasColumn('platform_commissions', 'settlement_id')) {
            Schema::table('platform_commissions', function (Blueprint $table) {
                $table->dropForeign(['settlement_id']);
                $table->dropColumn('settlement_id');
            });
        }

        Schema::dropIfExists('commission_settlements');
    }
};
