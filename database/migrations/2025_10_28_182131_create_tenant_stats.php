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
        Schema::create('tenant_stats', function (Blueprint $table) {
            $table->uuid("id")->primary();
            // Tenants use string UUID IDs, so use string FK
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->integer('total_products')->default(0);
            $table->integer('total_orders')->default(0);
            $table->integer('total_reviews')->default(0);
            $table->decimal('avg_rating', 3, 2)->default(0);
            $table->integer('total_clicks')->default(0);
            $table->integer('total_views')->default(0);
            $table->integer('total_favorites')->default(0);
            $table->date('stat_date');
            $table->timestamps();

            $table->unique(['tenant_id', 'stat_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_stats');
    }
};
