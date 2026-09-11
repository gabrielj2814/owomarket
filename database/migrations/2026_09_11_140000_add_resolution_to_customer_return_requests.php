<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reclamaciones: resolucion, reloj y medicion (subsistema 5, fases A y B).
 *
 * La tabla existia desde el 19/08/2026 y **el cliente ya creaba solicitudes que nadie
 * resolvia**: entraban y se quedaban ahi para siempre. Esto le añade lo que le faltaba para
 * ser un expediente y no un buzon.
 *
 * ## `tenant_order_id` es la columna que conecta esto con el dinero
 *
 * Las reclamaciones se indexaban por pedido CENTRAL mas producto, pero el dinero vive por
 * pedido DE TIENDA (`platform_commissions.order_id`). Sin esta columna, aprobar una
 * reclamacion no sabria que comision revertir -- o peor, revertiria la equivocada.
 *
 * Ademas es lo que deja el modelo listo para el escaparate: una venta de tienda no tiene
 * pedido central, pero si tiene pedido de tienda. Hoy el comprador del escaparate no tiene
 * ninguna pantalla desde la que reclamar --no existe una vista de «mis pedidos» en la
 * tienda-- asi que la puerta sigue entornada; pero el dia que exista, el modelo ya la admite.
 *
 * ## Los tres campos de medicion
 *
 * `amount`, `delivered_at` y `resolved_at` estaban anotados como pendientes en la decision de
 * garantias. Sin ellos, la revision a los 90 dias no puede responder cuanto dinero mueven las
 * reclamaciones, cuanto tiempo hay que retener, ni cuanto tarda una tienda en responder --que
 * es la señal de la que depende toda la reputacion (fase C)--.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_return_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('customer_return_requests', 'tenant_order_id')) {
                $table->string('tenant_order_id')->nullable()->after('order_number');
                $table->index('tenant_order_id', 'crr_tenant_order_index');
            }

            // MEDICION. Ver el bloque de arriba: sin estos tres, la revision a 90 dias no
            // puede responder nada.
            if (! Schema::hasColumn('customer_return_requests', 'amount')) {
                // En la misma unidad que `order_total`: dolares. El saldo se valora despues a
                // la tasa congelada de la venta, como todo lo demas.
                $table->decimal('amount', 10, 2)->default(0)->after('product_name');
            }
            if (! Schema::hasColumn('customer_return_requests', 'delivered_at')) {
                $table->timestamp('delivered_at')->nullable()->after('amount');
            }
            if (! Schema::hasColumn('customer_return_requests', 'resolved_at')) {
                $table->timestamp('resolved_at')->nullable()->after('status');
            }

            if (! Schema::hasColumn('customer_return_requests', 'resolved_by')) {
                // 'merchant' | 'timeout' | 'admin'. Distinguirlos no es contabilidad ociosa:
                // «resuelta por silencio» es exactamente la señal con la que la fase C
                // calculara la reputacion, y sin separarla de una respuesta real la tienda
                // que ignora y la que atiende puntuan igual.
                $table->string('resolved_by', 20)->nullable()->after('resolved_at');
            }
            if (! Schema::hasColumn('customer_return_requests', 'resolution_notes')) {
                $table->text('resolution_notes')->nullable()->after('resolved_by');
            }

            // FASE B: cuanto puso la plataforma de su bolsillo porque el saldo de la tienda no
            // daba. Es el numero que hay que vigilar contra el techo mensual de alarma.
            if (! Schema::hasColumn('customer_return_requests', 'platform_covered_amount')) {
                $table->decimal('platform_covered_amount', 10, 2)->default(0)->after('resolution_notes');
            }

            // Para el comando que resuelve por silencio: lo abierto, lo mas viejo primero.
            $table->index(['status', 'created_at'], 'crr_abiertas_index');
        });
    }

    public function down(): void
    {
        Schema::table('customer_return_requests', function (Blueprint $table) {
            $table->dropIndex('crr_abiertas_index');

            if (Schema::hasColumn('customer_return_requests', 'tenant_order_id')) {
                $table->dropIndex('crr_tenant_order_index');
            }

            foreach ([
                'tenant_order_id', 'amount', 'delivered_at', 'resolved_at',
                'resolved_by', 'resolution_notes', 'platform_covered_amount',
            ] as $columna) {
                if (Schema::hasColumn('customer_return_requests', $columna)) {
                    $table->dropColumn($columna);
                }
            }
        });
    }
};
