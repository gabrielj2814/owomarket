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
        Schema::create('product_images', function (Blueprint $table) {
            $table->uuid("id")->primary();
            // $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('product_id');
            $table->foreign('product_id')->references('id')->on('products');
            $table->string('image_path');
            $table->string('alt_text')->nullable();
            $table->boolean('is_default')->default(false);
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }
};
