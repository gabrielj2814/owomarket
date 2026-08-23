<?php

declare(strict_types=1);

namespace Src\Tenant\Application\UseCase;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Src\Monetization\Application\Service\TenantAvailableBalance;
use Src\Monetization\Infrastructure\Eloquent\Models\CommissionSettlement;
use Src\Tenant\Application\Service\TenantOwnershipVerifier;

final class CreateTenantOwnerPayoutRequestUseCase
{
    public function __construct(
        private readonly TenantOwnershipVerifier $ownership,
        private readonly TenantAvailableBalance $balance
    ) {}

    /**
     * @param array{
     *     tenant_id: string,
     *     amount: float,
     *     payment_method: string,
     *     payment_details: array<string, mixed>,
     *     notes?: string|null
     * } $data
     */
    public function execute(string $userId, array $data): CommissionSettlement
    {
        // 1. El solicitante debe ser propietario de la tienda. Lanza 404 si no existe
        //    y 403 si existe pero es de otro comerciante.
        $this->ownership->ensureOwns($userId, $data['tenant_id']);

        if ($data['amount'] <= 0) {
            throw new Exception('El monto a retirar debe ser mayor a 0.', 422);
        }

        return DB::transaction(function () use ($userId, $data) {
            /*
             * 2. El importe solicitado no puede superar el saldo disponible de la tienda.
             *
             * Hallazgo T1: recalcular dentro de la transacción no bastaba. Sin bloqueo, dos
             * peticiones simultáneas leen el mismo saldo, las dos pasan y las dos se crean
             * — el hallazgo C3 con otro nombre, y B3/C6 antes que él. `lock: true` bloquea
             * las filas de retiros de esta tienda mientras dure la transacción.
             *
             * La fórmula ya no vive aquí: la comparte con la aprobación, que es donde el
             * dinero sale de verdad y donde no se comprobaba nada.
             */
            $availableBalance = $this->balance->requestable($data['tenant_id'], lock: true);

            if ($data['amount'] > $availableBalance) {
                throw new Exception(
                    sprintf(
                        'El monto solicitado (%.2f USD) supera tu saldo disponible (%.2f USD).',
                        $data['amount'],
                        $availableBalance
                    ),
                    422
                );
            }

            $settlementNumber = 'PAY-'.date('Ymd').'-'.strtoupper(Str::random(6));

            return CommissionSettlement::create([
                'id' => (string) Str::uuid(),
                'settlement_number' => $settlementNumber,
                'tenant_id' => $data['tenant_id'],
                'type' => 'payout',
                'gross_sales_amount' => $data['amount'],
                'commission_amount' => 0.00,
                'net_amount' => $data['amount'],
                'currency' => 'USD',
                'status' => 'pending',
                'payment_method' => $data['payment_method'],
                'notes' => $data['notes'] ?? 'Solicitud de retiro generada desde la Billetera Central',
                'metadata' => [
                    'user_id' => $userId,
                    'payment_details' => $data['payment_details'],
                    'requested_at' => now()->toIso8601String(),
                ],
            ]);
        });
    }
}
