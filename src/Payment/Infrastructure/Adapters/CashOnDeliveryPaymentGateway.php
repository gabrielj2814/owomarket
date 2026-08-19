<?php

declare(strict_types=1);

namespace Src\Payment\Infrastructure\Adapters;

use Illuminate\Support\Str;
use Src\Payment\Domain\Contracts\PaymentGatewayInterface;
use Src\Payment\Domain\ValueObjects\PaymentResult;
use Src\Payment\Domain\ValueObjects\RefundResult;
use Src\Payment\Domain\ValueObjects\WebhookResult;

final class CashOnDeliveryPaymentGateway implements PaymentGatewayInterface
{
    public function __construct(
        private readonly array $config = []
    ) {}

    public function getIdentifier(): string
    {
        return 'cash_on_delivery';
    }

    public function getDisplayName(): string
    {
        return 'Pago Contra Entrega / Efectivo';
    }

    public function isOffline(): bool
    {
        return true;
    }

    public function charge(array $paymentData): PaymentResult
    {
        $txId = 'TX-COD-'.strtoupper(Str::random(10));
        $instructions = $this->config['instructions']
            ?? 'El pago se realizará en efectivo al momento de recibir el pedido.';

        return PaymentResult::pending(
            transactionId: $txId,
            instructions: $instructions,
            redirectUrl: null,
            message: 'Pago contra entrega registrado con éxito',
            rawResponse: ['instructions' => $instructions, 'transaction_id' => $txId]
        );
    }

    public function refund(string $transactionId, float $amount, ?string $reason = null): RefundResult
    {
        $refundId = 'REF-COD-'.strtoupper(Str::random(8));

        return RefundResult::success(
            refundId: $refundId,
            amount: $amount,
            message: "Reembolso en efectivo registrado para la transacción {$transactionId}",
            rawResponse: ['refund_id' => $refundId, 'reason' => $reason]
        );
    }

    public function handleWebhook(array $payload, array $headers = []): WebhookResult
    {
        return WebhookResult::ignored('cash_on_delivery_webhook', $payload);
    }
}
