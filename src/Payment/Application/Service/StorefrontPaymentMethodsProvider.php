<?php

declare(strict_types=1);

namespace Src\Payment\Application\Service;

use Src\ExchangeRate\Application\UseCase\GetActiveExchangeRateUseCase;
use Src\TenantSettings\Application\Repositories\TenantSettingsRepositoryInterface;
use Src\TenantSettings\Domain\ValueObjects\SettingGroup;
use Throwable;

/**
 * Construye la lista de métodos de pago que se ofrecen en el checkout de una
 * tienda, a partir de los datos de cobro que el comerciante haya configurado
 * (grupo de settings `payment`).
 *
 * Hallazgo G1: antes esta lista se armaba con literales de demostración
 * dentro de ViewCheckoutTenantGETController:
 *
 *     'document_id'    => 'J-50123456-0',
 *     'binance_pay_id' => '284759302',
 *     'qr_code'        => 'https://api.qrserver.com/...binancepay://pay?id=284759302',
 *     'exchange_rate_ves' => 40.50,
 *
 * El comprador elegía Pago Móvil, veía un RIF y un teléfono que no eran los
 * de la tienda, y transfería el dinero a un tercero. Con Binance pagaba a un
 * Pay ID ajeno y escaneaba un QR generado por un servicio externo.
 *
 * Regla de esta clase: **un método de pago que no esté completamente
 * configurado NO se ofrece**. Es preferible que el comprador vea una opción
 * menos a que envíe dinero a una cuenta equivocada.
 */
final class StorefrontPaymentMethodsProvider
{
    public function __construct(
        private readonly TenantSettingsRepositoryInterface $settings,
        private readonly GetActiveExchangeRateUseCase $getActiveExchangeRate
    ) {}

    /**
     * @param  array<string, mixed>  $storeSettings  Mapa de settings generales de la tienda.
     * @return array<int, array<string, mixed>>
     */
    public function forStore(array $storeSettings): array
    {
        $payment = $this->paymentSettings();
        $methods = [];

        // --- Pago Móvil -----------------------------------------------------
        // Requiere banco, documento y teléfono receptor. Sin los tres, no hay
        // forma de que el comprador pague correctamente.
        $bankName = $this->value($payment, 'pago_movil_bank_name');
        $documentId = $this->value($payment, 'pago_movil_document_id');
        $phone = $this->value($payment, 'pago_movil_phone');

        if ($bankName !== null && $documentId !== null && $phone !== null) {
            $method = [
                'id' => 'pago_movil',
                'name' => 'Pago Móvil Interbancario (VES)',
                'description' => 'Transfiere en Bolívares (VES) al instante a través de Pago Móvil e ingresa el número de referencia.',
                'bank_name' => $bankName,
                'document_id' => $documentId,
                'phone' => $phone,
                'holder_name' => $this->value($payment, 'pago_movil_holder_name')
                    ?? ($storeSettings['store_name'] ?? null),
            ];

            // La tasa sale del módulo ExchangeRate, no de un literal. Si no hay
            // tasa activa se omite: el frontend no muestra el monto en bolívares
            // en vez de mostrar uno inventado (hallazgos D3 y G13).
            $rate = $this->activeVesRate();
            if ($rate !== null) {
                $method['exchange_rate_ves'] = $rate;
            }

            $methods[] = $method;
        }

        // --- Binance Pay ----------------------------------------------------
        $binancePayId = $this->value($payment, 'binance_pay_id');

        if ($binancePayId !== null) {
            $methods[] = [
                'id' => 'binance_pay',
                'name' => 'Binance Pay / USDT (Cripto)',
                'description' => 'Pago instantáneo en USDT sin comisiones de red usando Binance Pay ID.',
                'binance_pay_id' => $binancePayId,
                'crypto_currency' => 'USDT',
                // El QR se omite deliberadamente: el anterior lo generaba
                // api.qrserver.com, un tercero al que se le filtraba el
                // identificador de cobro del comercio. Si se quiere volver a
                // ofrecer, debe generarse en el propio servidor.
                'qr_code' => $this->value($payment, 'binance_qr_url'),
            ];
        }

        // --- Transferencia bancaria ----------------------------------------
        $transferInstructions = $this->value($payment, 'bank_transfer_instructions');

        if ($transferInstructions !== null) {
            $methods[] = [
                'id' => 'bank_transfer',
                'name' => 'Transferencia Bancaria Directa',
                'description' => 'Realiza tu pago directamente en nuestra cuenta bancaria. Tu pedido será procesado tras verificar el comprobante.',
                'instructions' => $transferInstructions,
            ];
        }

        // --- Contra entrega -------------------------------------------------
        // No necesita datos de cobro, pero sí que el comercio lo habilite.
        if ($this->isEnabled($payment, 'cash_on_delivery_enabled')) {
            $methods[] = [
                'id' => 'cash_on_delivery',
                'name' => 'Pago Contra Entrega / Efectivo',
                'description' => 'Paga en efectivo al momento de recibir tu paquete en tu domicilio.',
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
            $map = [];
            foreach ($this->settings->listByGroup(SettingGroup::payment()) as $setting) {
                $map[$setting->key()->value()] = $setting->value();
            }

            return $map;
        } catch (Throwable) {
            // Tienda sin tabla de settings o sin configuración: se devuelve
            // vacío, lo que hace que no se ofrezca ningún método de cobro.
            return [];
        }
    }

    /**
     * @param  array<string, string>  $payment
     */
    private function value(array $payment, string $key): ?string
    {
        $raw = $payment[$key] ?? null;

        if ($raw === null) {
            return null;
        }

        $trimmed = trim((string) $raw);

        return $trimmed !== '' ? $trimmed : null;
    }

    /**
     * @param  array<string, string>  $payment
     */
    private function isEnabled(array $payment, string $key): bool
    {
        $raw = $this->value($payment, $key);

        return $raw !== null && in_array(strtolower($raw), ['1', 'true', 'yes', 'si', 'sí'], true);
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
