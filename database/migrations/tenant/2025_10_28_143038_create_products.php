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
        Schema::create('products', function (Blueprint $table) {
            $table->uuid("id")->primary();
            // $table->foreignId('category_id')->nullable()->constrained()->onDelete('cascade');
            // $table->foreignId('brand_id')->nullable()->constrained()->onDelete('set null');
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku')->unique();
            $table->decimal('price', 10, 2);

            $table->integer('quantity')->default(0);
            $table->integer('min_quantity')->default(1);
            $table->integer('max_quantity')->default(100);
            $table->boolean('track_quantity')->default(true);
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_featured')->default(false);

            $table->text('description')->nullable();
            $table->text('short_description')->nullable();
            $table->decimal('compare_price', 10, 2)->nullable();
            $table->decimal('cost_price', 10, 2)->nullable();
            $table->string('barcode')->nullable();

            $table->decimal('weight', 8, 2)->nullable();
            $table->decimal('height', 8, 2)->nullable();
            $table->decimal('width', 8, 2)->nullable();
            $table->decimal('length', 8, 2)->nullable();

            $table->boolean('is_digital')->default(false);
            $table->string('digital_product_url')->nullable();

            $table->date('published_at')->nullable();
            $table->json('seo')->nullable();
            $table->json('specifications')->nullable();

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
        Schema::dropIfExists('products');
    }
};
