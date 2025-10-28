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
        Schema::create('tenant_tag_tenant', function (Blueprint $table) {
            $table->id();
            // Tenants use string UUID IDs, so use string FK
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreignId('tenant_tag_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['tenant_id', 'tenant_tag_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_tag_tenant');
    }
};
