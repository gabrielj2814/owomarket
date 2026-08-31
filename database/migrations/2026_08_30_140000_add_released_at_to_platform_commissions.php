<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 4b de `planes/por_hacer/PLAN_WALLET_Y_RETIROS.md`: el saldo no es retirable hasta que
 * el pedido llega a `delivered`.
 *
 * Protege del reembolso posterior al retiro. Si la plataforma paga al comerciante y el
 * comprador reclama después, el dinero ya salió y recuperarlo es perseguirlo. Reteniendo
 * hasta la entrega, la ventana se cierra sola: `OrderStatus::canBeRefunded()` sigue
 * admitiendo `delivered`, pero el caso frecuente --el paquete que nunca llegó-- deja de
 * poder cobrarse por adelantado.
 *
 * **Por qué una columna y no una consulta.** La comisión vive en la base central y el estado
 * del pedido en la de cada tienda. Preguntarlo al calcular el saldo obligaría a entrar en la
 * base de cada inquilino en cada consulta de wallet. Se anota aquí cuando ocurre, que es una
 * escritura por pedido en vez de una lectura por consulta.
 *
 * `nullable` porque «todavía no entregado» es el estado normal de una comisión recién nacida.
 * Las filas anteriores se quedan a null: no consta que se entregaran, y dar por entregado lo
 * que no consta sería liberar dinero por si acaso.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('platform_commissions', 'released_at')) {
            return;
        }

        Schema::table('platform_commissions', function (Blueprint $table) {
            $table->timestamp('released_at')->nullable()->after('status');
            $table->index('released_at');
        });
    }

    public function down(): void
    {
        Schema::table('platform_commissions', function (Blueprint $table) {
            $table->dropIndex(['released_at']);
            $table->dropColumn('released_at');
        });
    }
};
