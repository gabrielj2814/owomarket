<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 1 de `planes/por_hacer/PLAN_WALLET_Y_RETIROS.md`.
 *
 * La wallet de cada tienda guarda el saldo **en bolívares, congelado a la tasa de la venta**:
 * la plataforma recibe X Bs del comprador y le debe exactamente esos X Bs al comerciante, así
 * que su posición queda cuadrada pase lo que pase con la tasa. La alternativa —saldo en USD
 * convertido el día del retiro— dejaría el riesgo cambiario sobre la plataforma, que recibe
 * bolívares y debería dólares.
 *
 * Congelar exige capturar la tasa **en el momento de la venta**, y por eso esta columna es lo
 * único de todo el plan que no se puede añadir después: cada venta que ocurra sin su tasa es
 * una venta cuyo saldo ya no se puede congelar.
 *
 * No hacen falta columnas de importes en bolívares. Salen derivados y sumables en SQL:
 *
 *     SUM((order_total - commission_amount) * exchange_rate)
 *
 * `nullable` a propósito: si no hay tasa activa, la venta no puede caerse por eso. Una
 * comisión sin tasa queda sin valorar, y qué hacer con ella es la Fase 2.
 *
 * Sin relleno para las filas existentes, al contrario que en la migración de
 * `central_order_id`: allí el dato correcto ya estaba escrito en `metadata` y sólo había que
 * recuperarlo. Aquí no existe en ninguna parte, y **inventar la tasa histórica de una venta
 * pasada sería fabricar el importe de una deuda**. Se quedan a null.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_commissions', function (Blueprint $table) {
            $table->decimal('exchange_rate', 18, 4)->nullable()->after('currency');
        });
    }

    public function down(): void
    {
        Schema::table('platform_commissions', function (Blueprint $table) {
            $table->dropColumn('exchange_rate');
        });
    }
};
