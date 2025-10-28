<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTenantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->string('id')->primary();

            // your custom columns may go here
            $table->string('name');
            $table->string('slug')->unique();
            $table->json('data'); // Configuraciones de la tienda
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->string('theme')->default('default');
            $table->string('locale')->default('es');
            $table->string('timezone')->default('UTC');
            $table->string('currency')->default('USD');

            $table->timestamps();
            $table->json('data')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
}
