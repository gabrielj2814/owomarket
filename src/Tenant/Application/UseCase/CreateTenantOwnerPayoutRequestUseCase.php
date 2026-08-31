<?php

declare(strict_types=1);

namespace Src\Tenant\Application\UseCase;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Src\Monetization\Application\Service\TenantAvailableBalance;
use Src\Monetization\Infrastructure\Eloquent\Models\CommissionSettlement;
use Src\Payment\Infrastructure\Eloquent\Models\CentralSetting;
use Src\Tenant\Application\Service\TenantOwnershipVerifier;
use Throwable;

final class CreateTenantOwnerPayoutRequestUseCase
{
    public function __construct(
        private readonly TenantOwnershipVerifier $ownership,
        private readonly TenantAvailableBalance $balance
    ) {}

    /**
     * @param array{
     *     tenant_id: string,
     *     amount: float,   // en bolivares
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
                        'El monto solicitado (Bs. %s) supera tu saldo disponible (Bs. %s).',
                        number_format($data['amount'], 2, ',', '.'),
                        number_format($availableBalance, 2, ',', '.')
                    ),
                    422
                );
            }

            $settlementNumber = 'PAY-'.date('Ymd').'-'.strtoupper(Str::random(6));

            // Fase 4c: cada tienda cobra en el banco que quiera, pero si hace falta una
            // transferencia interbancaria su coste lo asume quien eligio esa via.
            $comisionTransferencia = $this->comisionPorBanco($data['payment_details']);

            return CommissionSettlement::create([
                'id' => (string) Str::uuid(),
                'settlement_number' => $settlementNumber,
                'tenant_id' => $data['tenant_id'],
                'type' => 'payout',
                // Los tres dejan de ser el mismo numero: sale de la wallet lo PEDIDO, la
                // plataforma se queda la comision, y el comerciante recibe la diferencia.
                'gross_sales_amount' => $data['amount'],
                'commission_amount' => 0.00,
                'transfer_fee' => $comisionTransferencia,
                'net_amount' => max(0.0, $data['amount'] - $comisionTransferencia),
                // El comerciante retira BOLIVARES: es lo que el comprador pago y lo que la
                // plataforma recibio. El dolar solo es la unidad en la que se puso el precio.
                // Antes esto decia 'USD' mientras la pantalla enseñaba bolivares.
                'currency' => 'VES',
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

    /**
     * Coste de transferir a este banco, en bolivares.
     *
     * Cero si el destino es el mismo banco de la plataforma --no hay transferencia
     * interbancaria que pagar-- y el importe configurado en cualquier otro caso. Sin banco
     * declarado se cobra: no poder comprobarlo no es motivo para regalarlo.
     *
     * Los bancos se comparan por nombre normalizado: minusculas, espacios colapsados y el
     * prefijo "banco" fuera, que es el caso que de verdad se da --el comerciante escribe
     * "Banco Mercantil" donde la plataforma tiene "Mercantil"--.
     *
     * ponytail: sigue siendo comparacion de texto libre, asi que un alias real ("BOD" contra
     * "Banco Occidental de Descuento") no casaria. La salida es una lista de bancos con codigo
     * en el formulario; no se construye hoy porque no hay evidencia de que haga falta, y el
     * coste de equivocarse es cobrar de mas una comision, no perder dinero.
     *
     * @param  array<string, mixed>  $paymentDetails
     */
    private function comisionPorBanco(array $paymentDetails): float
    {
        $ajustes = $this->ajustesDeLaPlataforma();

        $comision = (float) ($ajustes['central_interbank_transfer_fee'] ?? 0.0);

        if ($comision <= 0.0) {
            return 0.0;
        }

        $bancoPlataforma = $this->normalizar((string) ($ajustes['central_pago_movil_bank_name'] ?? ''));
        $bancoDestino = $this->normalizar((string) ($paymentDetails['bank'] ?? ''));

        if ($bancoPlataforma === '' || $bancoDestino === '') {
            return $comision;
        }

        return $bancoDestino === $bancoPlataforma ? 0.0 : $comision;
    }

    /** @return array<string, string> */
    private function ajustesDeLaPlataforma(): array
    {
        try {
            return CentralSetting::query()
                ->where('group', 'payment')
                ->pluck('value', 'key')
                ->all();
        } catch (Throwable) {
            // Sin ajustes no se puede saber si hay comision, y cobrar un importe que nadie
            // configuro seria peor que no cobrarlo.
            return [];
        }
    }

    private function normalizar(string $valor): string
    {
        $limpio = mb_strtolower(trim(preg_replace('/\s+/', ' ', $valor) ?? ''));

        // "Banco Mercantil" y "Mercantil" son el mismo banco escrito por dos personas
        // distintas. Solo se quita como prefijo: un banco que se llamara "Banco Banco" --no
        // existe, pero la regla no puede depender de eso-- conserva el segundo.
        return preg_replace('/^banco\s+/', '', $limpio) ?? $limpio;
    }
}
