<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 4 de `planes/por_hacer/PLAN_WALLET_Y_RETIROS.md`: la tasa a la que compró el cliente,
 * guardada en su pedido.
 *
 * La Fase 1 la capturó en la comisión, que basta para calcular la wallet del comerciante pero
 * no dice nada del comprador. El precio se pone en dólares, al comprador se le muestra su
 * equivalente en bolívares a la tasa del día, y **paga bolívares** — pero esa cifra no
 * quedaba registrada en ninguna parte: existía sólo en su extracto bancario.
 *
 * `nullable` por el mismo motivo que en las comisiones: si el BCV no ha sincronizado, el
 * pedido no puede caerse por eso.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('orders', 'exchange_rate')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('exchange_rate', 18, 4)->nullable()->after('currency');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('exchange_rate');
        });
    }
};
