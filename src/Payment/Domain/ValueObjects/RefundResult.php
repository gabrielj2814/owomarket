<?php

declare(strict_types=1);

namespace Src\Payment\Domain\ValueObjects;

final class RefundResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $refundId = null,
        public readonly float $refundedAmount = 0.0,
        public readonly ?array $rawResponse = null,
        public readonly ?string $message = null
    ) {}

    public static function success(
        string $refundId,
        float $amount,
        ?string $message = 'Reembolso procesado exitosamente',
        ?array $rawResponse = null
    ): self {
        return new self(
            success: true,
            refundId: $refundId,
            refundedAmount: $amount,
            rawResponse: $rawResponse,
            message: $message
        );
    }

    public static function failed(
        string $message = 'No se pudo procesar el reembolso',
        ?array $rawResponse = null
    ): self {
        return new self(
            success: false,
            rawResponse: $rawResponse,
            message: $message
        );
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'refund_id' => $this->refundId,
            'refunded_amount' => $this->refundedAmount,
            'message' => $this->message,
            'raw_response' => $this->rawResponse,
        ];
    }
}
