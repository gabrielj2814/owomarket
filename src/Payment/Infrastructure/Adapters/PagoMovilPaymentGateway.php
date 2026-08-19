<?php

declare(strict_types=1);

namespace Src\Payment\Infrastructure\Adapters;

use Illuminate\Support\Str;
use Src\Payment\Domain\Contracts\PaymentGatewayInterface;
use Src\Payment\Domain\ValueObjects\PaymentResult;
use Src\Payment\Domain\ValueObjects\RefundResult;
use Src\Payment\Domain\ValueObjects\WebhookResult;

final class PagoMovilPaymentGateway implements PaymentGatewayInterface
{
    public function __construct(
        private readonly array $config = []
    ) {}

    public function getIdentifier(): string
    {
        return 'pago_movil';
    }

    public function getDisplayName(): string
    {
        return 'Pago Móvil Interbancario (VES)';
    }

    public function isOffline(): bool
    {
        return true;
    }

    public function charge(array $paymentData): PaymentResult
    {
        $reference = $paymentData['metadata']['reference_number']
            ?? $paymentData['metadata']['payment_details']['reference_number']
            ?? strtoupper(Str::random(8));

        $bankOrigin = $paymentData['metadata']['bank_origin']
            ?? $paymentData['metadata']['payment_details']['bank_origin']
            ?? 'Desconocido';

        $phoneOrigin = $paymentData['metadata']['phone_origin']
            ?? $paymentData['metadata']['payment_details']['phone_origin']
            ?? null;

        $txId = 'PM-' . $reference;

        $instructions = $this->config['instructions']
            ?? 'Pago Móvil registrado con la referencia: ' . $reference . '. Pendiente de conciliación bancaria.';

        return PaymentResult::pending(
            transactionId: $txId,
            instructions: $instructions,
            redirectUrl: null,
            message: 'Pago Móvil registrado exitosamente. Referencia: ' . $reference,
            rawResponse: [
                'gateway' => 'pago_movil',
                'reference_number' => $reference,
                'bank_origin' => $bankOrigin,
                'phone_origin' => $phoneOrigin,
                'currency' => $paymentData['currency'] ?? 'USD',
                'amount' => $paymentData['amount'] ?? 0.0,
                'transaction_id' => $txId,
            ]
        );
    }

    public function refund(string $transactionId, float $amount, ?string $reason = null): RefundResult
    {
        $refundId = 'REF-PM-' . strtoupper(Str::random(8));

        return RefundResult::success(
            refundId: $refundId,
            amount: $amount,
            message: "Reembolso de Pago Móvil procesado para {$transactionId}",
            rawResponse: ['refund_id' => $refundId, 'reason' => $reason]
        );
    }

    public function handleWebhook(array $payload, array $headers = []): WebhookResult
    {
        return WebhookResult::ignored('pago_movil_webhook', $payload);
    }
}
