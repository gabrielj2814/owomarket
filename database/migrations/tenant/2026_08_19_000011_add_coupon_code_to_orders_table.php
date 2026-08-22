<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hallazgo N27: `usage_limit_per_customer` existe en `coupons` desde el principio y **no
 * se aplica en ningun sitio**. `validateUsability()` solo comprueba el limite global.
 *
 * No se podia comprobar porque `orders` no guardaba que cupon se habia usado: la Fase 3.1
 * empezo a escribirlo en el `metadata` del pedido, pero contar sobre JSON es fragil y
 * lento. Con una columna indexada es un `count()`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'coupon_code')) {
                $table->string('coupon_code')->nullable()->after('discount_amount');
                $table->index(['coupon_code', 'customer_id']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'coupon_code')) {
                $table->dropIndex(['coupon_code', 'customer_id']);
                $table->dropColumn('coupon_code');
            }
        });
    }
};
