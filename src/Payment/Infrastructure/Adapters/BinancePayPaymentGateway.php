<?php

declare(strict_types=1);

namespace Src\Payment\Infrastructure\Adapters;

use Illuminate\Support\Str;
use Src\Payment\Domain\Contracts\PaymentGatewayInterface;
use Src\Payment\Domain\ValueObjects\PaymentResult;
use Src\Payment\Domain\ValueObjects\RefundResult;
use Src\Payment\Domain\ValueObjects\WebhookResult;

final class BinancePayPaymentGateway implements PaymentGatewayInterface
{
    public function __construct(
        private readonly array $config = []
    ) {}

    public function getIdentifier(): string
    {
        return 'binance_pay';
    }

    public function getDisplayName(): string
    {
        return 'Binance Pay / USDT';
    }

    public function isOffline(): bool
    {
        return false;
    }

    public function charge(array $paymentData): PaymentResult
    {
        $binanceId = $paymentData['metadata']['binance_id']
            ?? $paymentData['metadata']['payment_details']['binance_id']
            ?? null;

        $txHash = $paymentData['metadata']['transaction_hash']
            ?? $paymentData['metadata']['payment_details']['transaction_hash']
            ?? $paymentData['metadata']['reference_number']
            ?? strtoupper(Str::random(12));

        $txId = 'BN-' . $txHash;

        $merchantPayId = $this->config['merchant_pay_id'] ?? '284759302';
        $instructions = "Transfiere el monto exacto en USDT a través de Binance Pay al Pay ID: {$merchantPayId}. Tu orden será confirmada con el Hash/ID: {$txHash}.";

        return PaymentResult::pending(
            transactionId: $txId,
            instructions: $instructions,
            redirectUrl: null,
            message: 'Pago vía Binance Pay registrado exitosamente. ID/Hash: ' . $txHash,
            rawResponse: [
                'gateway' => 'binance_pay',
                'merchant_pay_id' => $merchantPayId,
                'customer_binance_id' => $binanceId,
                'transaction_hash' => $txHash,
                'crypto_currency' => 'USDT',
                'amount_usdt' => $paymentData['amount'] ?? 0.0,
                'transaction_id' => $txId,
            ]
        );
    }

    public function refund(string $transactionId, float $amount, ?string $reason = null): RefundResult
    {
        $refundId = 'REF-BN-' . strtoupper(Str::random(8));

        return RefundResult::success(
            refundId: $refundId,
            amount: $amount,
            message: "Reembolso cripto Binance Pay procesado para {$transactionId}",
            rawResponse: ['refund_id' => $refundId, 'reason' => $reason]
        );
    }

    public function handleWebhook(array $payload, array $headers = []): WebhookResult
    {
        // En caso de webhook de Binance Pay
        $status = $payload['status'] ?? 'UNKNOWN';
        if ($status === 'PAID' || $status === 'SUCCESS') {
            return WebhookResult::paid(
                transactionId: $payload['bizId'] ?? $payload['transaction_id'] ?? 'BN-WEBHOOK',
                gateway: 'binance_pay',
                payload: $payload
            );
        }

        return WebhookResult::ignored('binance_pay_webhook', $payload);
    }
}
