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
        Schema::create('central_home_banners', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('image_url');
            $table->string('link_url')->nullable();
            $table->string('badge_text')->nullable();
            $table->enum('position_type', ['hero_slider', 'top_promo', 'featured_grid', 'footer_banner'])->default('hero_slider');
            $table->integer('order_position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['position_type', 'is_active', 'order_position'], 'idx_banners_pos_active_ord');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('central_home_banners');
    }
};
