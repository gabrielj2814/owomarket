<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 1.1 — hallazgo C2: el despacho multi-tienda no era transaccional ni
 * idempotente.
 *
 * Escenario de la auditoría: carrito de 3 tiendas; la base de datos del
 * tenant 2 no responde. El tenant 1 ya tiene su pedido, su `payment` y su
 * comisión; el cliente recibe un error y reintenta. Se creaba un SEGUNDO
 * CentralOrder completo y el tenant 1 acababa con dos pedidos idénticos y dos
 * comisiones — se cobraba dos veces por una sola compra.
 *
 * Se atacan los dos niveles del problema:
 *
 *   1. `central_orders.idempotency_key` (único): dos envíos del mismo checkout
 *      con la misma clave devuelven el MISMO pedido en lugar de crear otro.
 *   2. `central_order_dispatches` (único por central_order_id + tenant_id):
 *      aunque el despacho se reintente, cada tienda recibe su pedido una sola
 *      vez. La garantía la da el índice único de la base de datos, no una
 *      comprobación en PHP que podría perder una carrera.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('central_orders', function (Blueprint $table) {
            $table->string('idempotency_key')->nullable()->unique()->after('order_number');
        });

        Schema::create('central_order_dispatches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('central_order_id');
            $table->string('tenant_id');
            $table->string('tenant_order_id')->nullable();
            $table->string('status', 30)->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamps();

            $table->foreign('central_order_id')->references('id')->on('central_orders')->onDelete('cascade');

            // La clave del asunto: una tienda no puede recibir dos veces el
            // mismo pedido central.
            $table->unique(['central_order_id', 'tenant_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('central_order_dispatches');

        Schema::table('central_orders', function (Blueprint $table) {
            $table->dropUnique(['idempotency_key']);
            $table->dropColumn('idempotency_key');
        });
    }
};
