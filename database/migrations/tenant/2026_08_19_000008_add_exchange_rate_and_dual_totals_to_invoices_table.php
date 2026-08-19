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
        if (! Schema::hasTable('invoices')) {
            return;
        }

        Schema::table('invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('invoices', 'exchange_rate')) {
                $table->decimal('exchange_rate', 16, 6)->nullable()->after('currency');
            }
            if (! Schema::hasColumn('invoices', 'subtotal_ves')) {
                $table->decimal('subtotal_ves', 16, 2)->nullable()->after('total');
            }
            if (! Schema::hasColumn('invoices', 'total_ves')) {
                $table->decimal('total_ves', 16, 2)->nullable()->after('subtotal_ves');
            }
            if (! Schema::hasColumn('invoices', 'subtotal_usd')) {
                $table->decimal('subtotal_usd', 16, 2)->nullable()->after('total_ves');
            }
            if (! Schema::hasColumn('invoices', 'total_usd')) {
                $table->decimal('total_usd', 16, 2)->nullable()->after('subtotal_usd');
            }
            if (! Schema::hasColumn('invoices', 'commission_amount')) {
                $table->decimal('commission_amount', 16, 2)->nullable()->after('total_usd');
            }
            if (! Schema::hasColumn('invoices', 'commission_currency')) {
                $table->string('commission_currency', 10)->nullable()->after('commission_amount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'exchange_rate',
                'subtotal_ves',
                'total_ves',
                'subtotal_usd',
                'total_usd',
                'commission_amount',
                'commission_currency',
            ]);
        });
    }
};
