<?php

declare(strict_types=1);

namespace Src\Monetization\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Monetization\Application\UseCases\GenerateTenantCommissionSettlementUseCase;
use Src\Shared\Helper\ApiResponse;

final class GenerateCommissionSettlementPOSTController
{
    public function __construct(
        private readonly GenerateTenantCommissionSettlementUseCase $useCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'tenant_id' => 'required|string',
            'type' => 'nullable|in:collection,payout',
            'notes' => 'nullable|string',
        ]);

        try {
            $settlement = $this->useCase->execute(
                tenantId: (string) $request->input('tenant_id'),
                type: (string) $request->input('type', 'collection'),
                notes: $request->input('notes')
            );

            return ApiResponse::success(
                data: $settlement,
                message: 'Liquidación de comisiones generada exitosamente',
                code: 201
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                code: (int) ($e->getCode() ?: 400)
            );
        }
    }
}
