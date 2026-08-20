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
        $isTesting = app()->runningUnitTests() || app()->environment('testing');
        $connections = array_unique(array_filter([
            config('database.default'),
            (! $isTesting && config('database.connections.central')) ? 'central' : null,
        ]));

        foreach ($connections as $conn) {
            $schema = Schema::connection($conn);
            if (! $schema->hasTable('central_customer_wishlists')) {
                $schema->create('central_customer_wishlists', function (Blueprint $table) {
                    $table->uuid('id')->primary();
                    $table->uuid('customer_id')->index();
                    $table->string('product_id')->index();
                    $table->string('tenant_id')->index();
                    $table->string('product_name');
                    $table->string('product_slug')->nullable();
                    $table->decimal('product_price', 10, 2);
                    $table->string('product_image')->nullable();
                    $table->timestamps();

                    $table->unique(['customer_id', 'product_id', 'tenant_id'], 'cust_prod_tenant_unique');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $isTesting = app()->runningUnitTests() || app()->environment('testing');
        $connections = array_unique(array_filter([
            config('database.default'),
            (! $isTesting && config('database.connections.central')) ? 'central' : null,
        ]));

        foreach ($connections as $conn) {
            Schema::connection($conn)->dropIfExists('central_customer_wishlists');
        }
    }
};
