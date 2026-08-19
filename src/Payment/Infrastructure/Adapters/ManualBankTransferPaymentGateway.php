<?php

declare(strict_types=1);

namespace Src\Payment\Infrastructure\Adapters;

use Illuminate\Support\Str;
use Src\Payment\Domain\Contracts\PaymentGatewayInterface;
use Src\Payment\Domain\ValueObjects\PaymentResult;
use Src\Payment\Domain\ValueObjects\RefundResult;
use Src\Payment\Domain\ValueObjects\WebhookResult;

final class ManualBankTransferPaymentGateway implements PaymentGatewayInterface
{
    public function __construct(
        private readonly array $config = []
    ) {}

    public function getIdentifier(): string
    {
        return 'manual_transfer';
    }

    public function getDisplayName(): string
    {
        return 'Transferencia Bancaria Directa';
    }

    public function isOffline(): bool
    {
        return true;
    }

    public function charge(array $paymentData): PaymentResult
    {
        $txId = 'TX-BT-'.strtoupper(Str::random(10));
        $instructions = $this->config['instructions']
            ?? 'Por favor realice la transferencia a la cuenta de la tienda y envíe el comprobante.';

        return PaymentResult::pending(
            transactionId: $txId,
            instructions: $instructions,
            redirectUrl: null,
            message: 'Instrucciones de transferencia generadas con éxito',
            rawResponse: ['instructions' => $instructions, 'transaction_id' => $txId]
        );
    }

    public function refund(string $transactionId, float $amount, ?string $reason = null): RefundResult
    {
        $refundId = 'REF-BT-'.strtoupper(Str::random(8));

        return RefundResult::success(
            refundId: $refundId,
            amount: $amount,
            message: "Reembolso manual registrado para la transacción {$transactionId}",
            rawResponse: ['refund_id' => $refundId, 'reason' => $reason]
        );
    }

    public function handleWebhook(array $payload, array $headers = []): WebhookResult
    {
        return WebhookResult::ignored('manual_transfer_webhook', $payload);
    }
}
