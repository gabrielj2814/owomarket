<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 4: la tasa a la que compró el cliente, en el pedido central.
 *
 * Es el pedido central el que fija la tasa de toda la compra: el comprador hace **un solo
 * pago** a la plataforma. Los pedidos de cada tienda que salen del despacho **heredan** esta
 * tasa en vez de capturar la suya; si cada uno cogiera la del momento en que el job corrió,
 * la suma de los bolívares de las tiendas no cuadraría con lo que el cliente pagó.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('central_orders', 'exchange_rate')) {
            return;
        }

        Schema::table('central_orders', function (Blueprint $table) {
            $table->decimal('exchange_rate', 18, 4)->nullable()->after('currency');
        });
    }

    public function down(): void
    {
        Schema::table('central_orders', function (Blueprint $table) {
            $table->dropColumn('exchange_rate');
        });
    }
};
