<?php

declare(strict_types=1);

namespace Src\Payment\Domain\Contracts;

use Src\Payment\Domain\ValueObjects\PaymentResult;
use Src\Payment\Domain\ValueObjects\RefundResult;
use Src\Payment\Domain\ValueObjects\WebhookResult;

interface PaymentGatewayInterface
{
    /**
     * Identificador único de la pasarela (ej: 'manual_transfer', 'cash_on_delivery', 'stripe', 'mercadopago').
     */
    public function getIdentifier(): string;

    /**
     * Nombre descriptivo legible para el usuario/cliente (ej: 'Transferencia Bancaria Directa', 'Tarjeta de Crédito').
     */
    public function getDisplayName(): string;

    /**
     * Indica si el método es manual/offline (sin redirección externa a pasarela digital).
     */
    public function isOffline(): bool;

    /**
     * Procesa una solicitud de pago o cargo.
     *
     * @param  array{
     *     order_id: ?string,
     *     amount: float,
     *     currency: string,
     *     customer_email: string,
     *     customer_name: string,
     *     description?: string,
     *     return_url?: string,
     *     cancel_url?: string,
     *     metadata?: array
     * }  $paymentData
     */
    public function charge(array $paymentData): PaymentResult;

    /**
     * Procesa un reembolso parcial o total.
     */
    public function refund(string $transactionId, float $amount, ?string $reason = null): RefundResult;

    /**
     * Interpreta y procesa el webhook asíncrono enviado por la pasarela de pagos.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $headers
     */
    public function handleWebhook(array $payload, array $headers = []): WebhookResult;
}
