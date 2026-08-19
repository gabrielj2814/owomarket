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
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('base_currency', 10)->default('USD');
            $table->string('target_currency', 10)->default('VES');
            $table->decimal('rate', 16, 6);
            $table->string('source', 30)->default('BCV_SCRAPING'); // 'BCV_SCRAPING', 'MANUAL_ADMIN', 'API_FALLBACK'
            $table->date('rate_date');
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['base_currency', 'target_currency', 'is_active'], 'idx_exchange_rate_lookup');
            $table->index('rate_date', 'idx_exchange_rate_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
