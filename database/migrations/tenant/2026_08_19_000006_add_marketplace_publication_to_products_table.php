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
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'is_published_central')) {
                $table->boolean('is_published_central')->default(false)->after('is_visible');
                $table->index('is_published_central');
            }
            if (! Schema::hasColumn('products', 'published_to_central_at')) {
                $table->timestamp('published_to_central_at')->nullable()->after('is_published_central');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'is_published_central')) {
                $table->dropIndex(['is_published_central']);
                $table->dropColumn('is_published_central');
            }
            if (Schema::hasColumn('products', 'published_to_central_at')) {
                $table->dropColumn('published_to_central_at');
            }
        });
    }
};
