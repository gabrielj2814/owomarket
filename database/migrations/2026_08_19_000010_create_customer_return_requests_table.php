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
            if (! $schema->hasTable('customer_return_requests')) {
                $schema->create('customer_return_requests', function (Blueprint $table) {
                    $table->uuid('id')->primary();
                    $table->string('order_id')->index();
                    $table->string('order_number');
                    $table->uuid('customer_id')->index();
                    $table->string('customer_email');
                    $table->string('product_id')->index();
                    $table->string('product_name');
                    $table->string('tenant_id')->index();
                    $table->string('reason');
                    $table->text('description');
                    $table->json('photos')->nullable();
                    $table->enum('status', ['requested', 'in_review', 'approved', 'rejected', 'refunded'])->default('requested');
                    $table->text('admin_notes')->nullable();
                    $table->timestamps();

                    $table->index(['customer_id', 'status']);
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
            Schema::connection($conn)->dropIfExists('customer_return_requests');
        }
    }
};
