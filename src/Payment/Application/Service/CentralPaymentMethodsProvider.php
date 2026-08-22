<?php

declare(strict_types=1);

namespace Src\Payment\Application\Service;

use Src\ExchangeRate\Application\UseCase\GetActiveExchangeRateUseCase;
use Src\Payment\Infrastructure\Eloquent\Models\CentralSetting;
use Throwable;

/**
 * Metodos de cobro del marketplace central, a partir de los datos que la plataforma
 * tenga configurados en `central_settings` (grupo `payment`).
 *
 * La Fase 0.5 (hallazgo G1) saco los datos bancarios de demostracion del checkout **del
 * inquilino**, pero el checkout **central** se quedo con los suyos incrustados en el TSX:
 *
 *     <div><strong>Banco:</strong> Banesco (0134)</div>
 *     <div><strong>C.I.:</strong> J-501234567</div>
 *     <div><strong>Telefono:</strong> 0412-9998877</div>
 *
 * En un pedido multi-tienda cobra la plataforma y luego liquida con cada comercio, asi
 * que esos datos tienen que ser los de la plataforma. Mientras estuvieron inventados, el
 * comprador transferia a una cuenta que no era de nadie.
 *
 * Misma regla que en el storefront: **un metodo que no este completamente configurado no
 * se ofrece**. Es preferible una opcion menos que un pago a una cuenta equivocada.
 */
final class CentralPaymentMethodsProvider
{
    public function __construct(
        private readonly GetActiveExchangeRateUseCase $getActiveExchangeRate
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        $settings = $this->paymentSettings();
        $methods = [];

        $bankName = $this->value($settings, 'central_pago_movil_bank_name');
        $documentId = $this->value($settings, 'central_pago_movil_document_id');
        $phone = $this->value($settings, 'central_pago_movil_phone');

        if ($bankName !== null && $documentId !== null && $phone !== null) {
            $method = [
                'id' => 'pago_movil',
                'name' => 'Pago Movil Interbancario (VES)',
                'bank_name' => $bankName,
                'document_id' => $documentId,
                'phone' => $phone,
                'holder_name' => $this->value($settings, 'central_pago_movil_holder_name'),
            ];

            // La tasa sale del modulo ExchangeRate. Si no hay tasa activa se omite, para
            // que el frontend no muestre un monto en bolivares inventado (D3, G9, G13).
            $rate = $this->activeVesRate();
            if ($rate !== null) {
                $method['exchange_rate_ves'] = $rate;
            }

            $methods[] = $method;
        }

        $binancePayId = $this->value($settings, 'central_binance_pay_id');

        if ($binancePayId !== null) {
            $methods[] = [
                'id' => 'binance_pay',
                'name' => 'Binance Pay / USDT (Cripto)',
                'binance_pay_id' => $binancePayId,
                'crypto_currency' => 'USDT',
            ];
        }

        return $methods;
    }

    /**
     * @return array<string, string>
     */
    private function paymentSettings(): array
    {
        try {
            return CentralSetting::query()
                ->where('group', 'payment')
                ->pluck('value', 'key')
                ->filter(fn ($value) => is_string($value) && trim($value) !== '')
                ->all();
        } catch (Throwable) {
            // Sin tabla o sin configuracion: no se ofrece ningun metodo.
            return [];
        }
    }

    /**
     * @param  array<string, string>  $settings
     */
    private function value(array $settings, string $key): ?string
    {
        $raw = $settings[$key] ?? null;

        return is_string($raw) && trim($raw) !== '' ? trim($raw) : null;
    }

    private function activeVesRate(): ?float
    {
        try {
            return $this->getActiveExchangeRate->execute()->getRate()->value();
        } catch (Throwable) {
            return null;
        }
    }
}
