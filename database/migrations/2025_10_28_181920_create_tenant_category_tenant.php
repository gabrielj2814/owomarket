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
        Schema::create('tenant_category_tenant', function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table->string("tenant_id");
            $table->string("tenant_category_id");
            $table->foreign('tenant_id')->references('id')->on('tenants')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('tenant_category_id')->references('id')->on('tenant_categories')->onUpdate('cascade')->onDelete('cascade');
            $table->boolean('is_primary')->default(false);
            $table->integer('position')->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'tenant_category_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_category_tenant');
    }
};
