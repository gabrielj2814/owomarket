<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 4c de `planes/por_hacer/PLAN_WALLET_Y_RETIROS.md`: la comisión por transferir a un banco
 * distinto del de la plataforma.
 *
 * Cada tienda cobra en el banco que quiera. Si al retirar hace falta una transferencia
 * interbancaria, su coste lo asume quien eligió esa vía.
 *
 * **Los tres importes dejan de ser el mismo número**, y eso es lo que obliga a la columna:
 *
 * | Campo | Qué es |
 * | :--- | :--- |
 * | `gross_sales_amount` | Lo que sale de la wallet |
 * | `transfer_fee` | Lo que se queda la plataforma para pagar la transferencia |
 * | `net_amount` | Lo que recibe el comerciante |
 *
 * Hasta ahora los tres coincidían en un retiro, así que daba igual cuál se restara del saldo.
 * Con la comisión ya no: restar `net_amount` le dejaría al comerciante la comisión como saldo
 * fantasma después de cada retiro, y repetible. Por eso `TenantAvailableBalance` pasa a restar
 * `gross_sales_amount`, que es lo que de verdad salió de su wallet.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('commission_settlements', 'transfer_fee')) {
            return;
        }

        Schema::table('commission_settlements', function (Blueprint $table) {
            $table->decimal('transfer_fee', 12, 2)->default(0.00)->after('commission_amount');
        });
    }

    public function down(): void
    {
        Schema::table('commission_settlements', function (Blueprint $table) {
            $table->dropColumn('transfer_fee');
        });
    }
};
