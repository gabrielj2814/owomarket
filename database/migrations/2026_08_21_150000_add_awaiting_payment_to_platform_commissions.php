<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hallazgo N15: la comision nace al **despachar** y no al **cobrar**.
 *
 * `CalculateAndRecordOrderCommissionUseCase` la crea con `status = 'pending'` sin mirar el
 * `payment_status`, que para pago_movil, transferencia manual y contra entrega es siempre
 * `pending`. Y la liquidacion se lleva todo lo que este en `pending`.
 *
 * La Fase 1.2 (hallazgo D2) tapo el sintoma: cancelar o reembolsar revierte la comision.
 * Pero **dependemos de que alguien cancele**: si el cliente sencillamente no paga y nadie
 * toca el pedido, a la tienda se le cobra igual una comision por una venta que no existio.
 *
 * Este estado nuevo separa las dos cosas: `awaiting_payment` es una comision devengada
 * pero **no cobrable**, y la liquidacion no la mira. Pasa a `pending` cuando el pago se
 * confirma.
 *
 * SQLite no tiene ENUM real —lo guarda como texto con un CHECK—, asi que la suite acepta
 * el valor nuevo sin tocar nada. En MySQL hay que ampliarlo de verdad.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('platform_commissions', function (Blueprint $table) {
            $table->enum('status', ['awaiting_payment', 'pending', 'collected', 'waived', 'refunded'])
                ->default('awaiting_payment')
                ->change();
        });
    }

    public function down(): void
    {
        // Deliberadamente vacia: volver al enum anterior fallaria en cuanto exista una
        // sola comision en `awaiting_payment`.
    }
};
