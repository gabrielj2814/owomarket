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
        Schema::create('invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('order_id')->nullable();
            $table->string('customer_id')->nullable();

            $table->string('invoice_number')->unique();     // Ej: FAC-2026-000001
            $table->string('status')->default('issued');     // 'draft', 'issued', 'paid', 'cancelled', 'refunded'
            $table->date('issue_date');
            $table->date('due_date')->nullable();
            $table->string('currency')->default('USD');

            // Importes contables inmutables
            $table->decimal('subtotal', 12, 2);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2);

            // Método y estado de pago
            $table->string('payment_method')->default('manual');
            $table->string('payment_status')->default('paid');
            $table->timestamp('paid_at')->nullable();

            // Snapshot inmutable de datos fiscales del cliente receptor
            $table->string('billing_customer_name');
            $table->string('billing_customer_tax_id')->nullable();
            $table->string('billing_customer_email');
            $table->json('billing_customer_address');

            // Snapshot inmutable de datos fiscales del emisor al momento de emisión
            $table->json('issuer_snapshot');

            $table->string('pdf_path')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
