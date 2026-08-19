<?php

declare(strict_types=1);

namespace Src\Monetization\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Monetization\Application\UseCases\ConfirmAndSettleCommissionUseCase;
use Src\Shared\Helper\ApiResponse;

final class ConfirmCommissionSettlementPOSTController
{
    public function __construct(
        private readonly ConfirmAndSettleCommissionUseCase $useCase
    ) {}

    public function __invoke(string $id, Request $request): JsonResponse
    {
        $request->validate([
            'payment_method' => 'nullable|string',
            'payment_reference' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        try {
            $settlement = $this->useCase->execute(
                settlementId: $id,
                paymentMethod: $request->input('payment_method'),
                paymentReference: $request->input('payment_reference'),
                notes: $request->input('notes')
            );

            return ApiResponse::success(
                data: $settlement,
                message: '¡Liquidación de comisiones confirmada y conciliada exitosamente!'
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                code: (int) ($e->getCode() ?: 400)
            );
        }
    }
}
