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
            if (! $schema->hasTable('central_customer_password_resets')) {
                $schema->create('central_customer_password_resets', function (Blueprint $table) {
                    $table->uuid('id')->primary();
                    $table->string('email');
                    $table->string('pin_code', 10);
                    $table->string('token', 64)->unique();
                    $table->timestamp('expires_at');
                    $table->timestamp('created_at')->useCurrent();

                    $table->index(['email', 'pin_code']);
                    $table->index(['email', 'expires_at']);
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('central_customer_password_resets');
    }
};
