<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El fondo de garantia (subsistema 4 de
 * `planes/anotaciones/DECISION_GARANTIAS_Y_RESPONSABILIDAD.md`).
 *
 * **No es un mecanismo nuevo: es hacer parcial la retencion que ya existia.**
 * `TenantAvailableBalance::netEarnings()` ya suma solo lo que tiene `released_at` vencido, o
 * sea que la retencion ya estaba ahi --pero era todo o nada--. Con estas dos columnas una
 * venta puede liberar el 90% y guardar el 10% un tiempo mas.
 *
 * Por eso no hay tabla de reservas: el saldo de una tienda tiene que salir de UN solo sitio.
 * Una segunda tabla que hubiera que restar aparte es exactamente como nacen las dos consultas
 * que responden a la misma pregunta y divergen --este repositorio ya tiene tres cicatrices de
 * eso, y la ultima costo un plan entero de reembolsos--.
 *
 * `reserve_amount` va en la MISMA unidad que `order_total` y `commission_amount` (dolares),
 * no en bolivares. El saldo se valora multiplicando por `exchange_rate`, la tasa congelada de
 * esa venta, igual que el resto de la formula: la plataforma retiene una parte de lo que
 * recibio, no un importe revalorizado a la tasa de hoy.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_commissions', function (Blueprint $table) {
            if (! Schema::hasColumn('platform_commissions', 'reserve_amount')) {
                $table->decimal('reserve_amount', 10, 2)->default(0)->after('commission_amount');
            }
            if (! Schema::hasColumn('platform_commissions', 'reserve_until')) {
                $table->timestamp('reserve_until')->nullable()->after('released_at');
                // Nombre corto explicito: el autogenerado se acercaria peligrosamente al
                // limite de 64 caracteres de MySQL, que ya rompio esto dos veces.
                $table->index(['tenant_id', 'reserve_until'], 'pc_reserva_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('platform_commissions', function (Blueprint $table) {
            if (Schema::hasColumn('platform_commissions', 'reserve_until')) {
                $table->dropIndex('pc_reserva_index');
                $table->dropColumn('reserve_until');
            }
            if (Schema::hasColumn('platform_commissions', 'reserve_amount')) {
                $table->dropColumn('reserve_amount');
            }
        });
    }
};
