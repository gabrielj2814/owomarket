<?php

declare(strict_types=1);

namespace Src\Monetization\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Monetization\Application\UseCases\SubscribeTenantToPlanUseCase;
use Src\Shared\Helper\ApiResponse;

final class SubscribeTenantPOSTController
{
    public function __construct(
        private readonly SubscribeTenantToPlanUseCase $useCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'plan' => 'required|string',
            'billing_cycle' => 'nullable|in:monthly,yearly',
        ]);

        $tenantId = tenant('id') ?? (string) $request->input('tenant_id');

        if (! $tenantId) {
            return ApiResponse::error(
                message: 'No se pudo identificar la tienda/inquilino.',
                code: 400
            );
        }

        try {
            $subscription = $this->useCase->execute(
                tenantId: (string) $tenantId,
                planSlugOrId: (string) $request->input('plan'),
                billingCycle: (string) $request->input('billing_cycle', 'monthly')
            );

            return ApiResponse::success(
                data: $subscription->load('plan'),
                message: '¡Suscripción actualizada exitosamente! Ahora disfrutas de una comisión reducida.',
                code: 200
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                code: (int) ($e->getCode() ?: 422)
            );
        }
    }
}
