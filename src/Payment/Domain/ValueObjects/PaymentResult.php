<?php

declare(strict_types=1);

namespace Src\Payment\Domain\ValueObjects;

final class PaymentResult
{
    public function __construct(
        public readonly bool $success,
        public readonly PaymentStatus $status,
        public readonly ?string $transactionId = null,
        public readonly ?string $redirectUrl = null,
        public readonly ?string $instructions = null,
        public readonly ?array $rawResponse = null,
        public readonly ?string $message = null
    ) {}

    public static function completed(
        ?string $transactionId = null,
        ?string $message = 'Pago completado con éxito',
        ?array $rawResponse = null
    ): self {
        return new self(
            success: true,
            status: PaymentStatus::completed(),
            transactionId: $transactionId,
            message: $message,
            rawResponse: $rawResponse
        );
    }

    public static function pending(
        ?string $transactionId = null,
        ?string $instructions = null,
        ?string $redirectUrl = null,
        ?string $message = 'Pago pendiente de confirmación',
        ?array $rawResponse = null
    ): self {
        return new self(
            success: true,
            status: PaymentStatus::pending(),
            transactionId: $transactionId,
            redirectUrl: $redirectUrl,
            instructions: $instructions,
            rawResponse: $rawResponse,
            message: $message
        );
    }

    public static function failed(
        string $message = 'El pago no pudo ser procesado',
        ?array $rawResponse = null
    ): self {
        return new self(
            success: false,
            status: PaymentStatus::failed(),
            rawResponse: $rawResponse,
            message: $message
        );
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'status' => $this->status->value(),
            'transaction_id' => $this->transactionId,
            'redirect_url' => $this->redirectUrl,
            'instructions' => $this->instructions,
            'message' => $this->message,
            'raw_response' => $this->rawResponse,
        ];
    }
}
