<?php

declare(strict_types=1);

namespace Src\Payment\Domain\ValueObjects;

final class WebhookResult
{
    public function __construct(
        public readonly bool $handled,
        public readonly string $eventType,
        public readonly ?PaymentStatus $paymentStatus = null,
        public readonly ?string $transactionId = null,
        public readonly ?string $orderId = null,
        public readonly array $payload = []
    ) {}

    public static function handled(
        string $eventType,
        ?PaymentStatus $status = null,
        ?string $transactionId = null,
        ?string $orderId = null,
        array $payload = []
    ): self {
        return new self(
            handled: true,
            eventType: $eventType,
            paymentStatus: $status,
            transactionId: $transactionId,
            orderId: $orderId,
            payload: $payload
        );
    }

    public static function ignored(string $eventType, array $payload = []): self
    {
        return new self(
            handled: false,
            eventType: $eventType,
            payload: $payload
        );
    }
}
