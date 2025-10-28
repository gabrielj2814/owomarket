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
        Schema::create('product_variant_attribute_values', function (Blueprint $table) {
            $table->id();
            // Usamos nombres explícitos para evitar identificadores demasiado largos en MySQL
            $table->unsignedBigInteger('product_variant_id');
            $table->unsignedBigInteger('product_attribute_value_id');
            $table->timestamps();

            $table->foreign('product_variant_id', 'pvav_variant_fk')
                ->references('id')->on('product_variants')
                ->onDelete('cascade');

            $table->foreign('product_attribute_value_id', 'pvav_attr_value_fk')
                ->references('id')->on('product_attribute_values')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variant_attribute_values');
    }
};
