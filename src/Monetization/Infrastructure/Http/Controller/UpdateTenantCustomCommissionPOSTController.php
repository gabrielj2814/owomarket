<?php

declare(strict_types=1);

namespace Src\Monetization\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Monetization\Application\UseCases\UpdateTenantCustomCommissionUseCase;
use Src\Shared\Helper\ApiResponse;

final class UpdateTenantCustomCommissionPOSTController
{
    public function __construct(
        private readonly UpdateTenantCustomCommissionUseCase $useCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'tenant_id' => 'required|string',
            'custom_commission_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        try {
            $rate = $request->has('custom_commission_rate') && $request->input('custom_commission_rate') !== null
                ? (float) $request->input('custom_commission_rate')
                : null;

            $tenant = $this->useCase->execute(
                tenantId: (string) $request->input('tenant_id'),
                customRate: $rate
            );

            return ApiResponse::success(
                data: [
                    'tenant_id' => $tenant->id,
                    'custom_commission_rate' => $rate,
                ],
                message: 'Comisión personalizada de la tienda actualizada exitosamente'
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                code: (int) ($e->getCode() ?: 422)
            );
        }
    }
}
