<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hallazgo T3 — solicitudes de cambio de plan del comerciante.
 *
 * Hasta ahora el boton «Mejorar Plan» de la pantalla de facturacion era un `alert()` que
 * decia «Solicitud registrada. Un asesor te contactara» y **no mandaba nada a ninguna
 * parte**: no existia endpoint ni tabla. El comerciante se quedaba esperando una llamada
 * que nadie iba a hacer.
 *
 * Tabla propia y no una fila `pending` en `tenant_subscriptions`, que era mas barato:
 * `ViewTenantOwnerBillingGETController` carga las suscripciones SIN filtrar por estado y
 * `CalculateAndRecordOrderCommissionUseCase` tambien las consulta, asi que una solicitud
 * pendiente se habria colado en el calculo de comisiones. Ahorrarse una tabla no compensa
 * meter la mano en el camino del dinero.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_plan_change_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tenant_id');

            // El plan que tenia al pedirlo, para que el panel muestre «de X a Y» aunque la
            // suscripcion cambie despues por otra via.
            $table->uuid('current_plan_id')->nullable();
            $table->uuid('requested_plan_id');
            $table->enum('billing_cycle', ['monthly', 'yearly'])->default('monthly');

            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('requested_by_user_id');
            $table->text('notes')->nullable();

            $table->string('resolved_by_user_id')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('requested_plan_id')->references('id')->on('subscription_plans')->onDelete('cascade');

            $table->index(['tenant_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_plan_change_requests');
    }
};
