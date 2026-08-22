<?php

declare(strict_types=1);

namespace Src\Monetization\Application\UseCases;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Src\Monetization\Infrastructure\Eloquent\Models\CommissionSettlement;
use Src\Monetization\Infrastructure\Eloquent\Models\PlatformCommission;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;

final class GenerateTenantCommissionSettlementUseCase
{
    /**
     * Genera una liquidación con las comisiones pendientes de una tienda.
     *
     * Hallazgo C3 — antes esto era una carrera abierta: se leían las comisiones
     * pendientes, se creaba la liquidación con los totales y DESPUÉS se
     * enlazaban con un `update` que no revalidaba `whereNull('settlement_id')`.
     * Sin transacción y sin bloqueo.
     *
     * Escenario de la auditoría: doble clic en «Generar liquidación» con $500
     * pendientes → se creaban SET-A y SET-B, ambas por $500; el segundo
     * `update` reasignaba todas las comisiones a SET-B, y SET-A quedaba
     * pendiente por $500 sin comisiones asociadas. Si el superadmin confirmaba
     * ambas, la plataforma registraba $1.000 cobrados sobre $500 reales.
     *
     * Ahora todo ocurre dentro de una transacción, las comisiones se bloquean
     * con `lockForUpdate()` antes de leerlas, y el enlace final revalida
     * `whereNull('settlement_id')` comprobando cuántas filas afectó.
     *
     * @param  string  $type  'collection' | 'payout'
     */
    public function execute(string $tenantId, string $type = 'collection', ?string $notes = null): CommissionSettlement
    {
        $tenant = Tenant::find($tenantId);
        if (! $tenant) {
            throw new Exception('Inquilino/Tienda no encontrado.', 404);
        }

        $settlement = DB::transaction(function () use ($tenantId, $type, $notes) {
            // El bloqueo es lo que serializa dos generaciones simultáneas: la
            // segunda espera aquí y, cuando entra, ya no ve comisiones libres.
            $pendingCommissions = PlatformCommission::where('tenant_id', $tenantId)
                ->where('status', 'pending')
                ->whereNull('settlement_id')
                ->lockForUpdate()
                ->get();

            if ($pendingCommissions->isEmpty()) {
                throw new Exception('No hay comisiones pendientes de liquidación para esta tienda.', 422);
            }

            $settlementNumber = 'SET-'.date('Ym').'-'.strtoupper(Str::random(6));

            $totalOrdersCount = $pendingCommissions->count();
            $grossSalesAmount = (float) $pendingCommissions->sum('order_total');
            $commissionAmount = (float) $pendingCommissions->sum('commission_amount');
            $netAmount = $type === 'collection' ? $commissionAmount : max(0.0, $grossSalesAmount - $commissionAmount);

            $created = CommissionSettlement::create([
                'id' => (string) Str::uuid(),
                'settlement_number' => $settlementNumber,
                'tenant_id' => $tenantId,
                'type' => in_array($type, ['collection', 'payout'], true) ? $type : 'collection',
                'total_orders_count' => $totalOrdersCount,
                'gross_sales_amount' => $grossSalesAmount,
                'commission_amount' => $commissionAmount,
                'net_amount' => $netAmount,
                'currency' => 'USD',
                'status' => 'pending',
                'notes' => $notes,
                'metadata' => [
                    'generated_at' => now()->toIso8601String(),
                    'orders_breakdown' => $pendingCommissions->pluck('order_number')->toArray(),
                ],
            ]);

            // El enlace revalida que sigan libres. Si otra transacción se
            // adelantó, el número de filas afectadas no coincide y se aborta
            // todo en lugar de emitir una liquidación con totales que no
            // corresponden a las comisiones realmente enlazadas.
            $linked = PlatformCommission::whereIn('id', $pendingCommissions->pluck('id'))
                ->whereNull('settlement_id')
                ->update(['settlement_id' => $created->id]);

            if ($linked !== $totalOrdersCount) {
                throw new Exception(
                    'Las comisiones cambiaron mientras se generaba la liquidación. Vuelve a intentarlo.',
                    409
                );
            }

            return $created;
        });

        return $settlement->fresh(['commissions', 'tenant']);
    }
}
