<?php

declare(strict_types=1);

namespace Src\Monetization\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Monetization\Application\UseCases\CreateTenantPlanChangeRequestUseCase;
use Src\Shared\Helper\ApiResponse;

final class CreateTenantPlanChangeRequestPOSTController
{
    public function __construct(
        private readonly CreateTenantPlanChangeRequestUseCase $useCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validado = $request->validate([
            'tenant_id' => 'required|string',
            'plan_id' => 'required|string',
            'billing_cycle' => 'nullable|in:monthly,yearly',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            // La identidad sale de la sesion, nunca del cuerpo. El caso de uso comprueba
            // ademas que esa tienda sea suya.
            $solicitud = $this->useCase->execute((string) auth()->id(), $validado);

            return ApiResponse::success(
                data: [
                    'id' => $solicitud->id,
                    'status' => $solicitud->status,
                    'requested_plan_id' => $solicitud->requested_plan_id,
                ],
                message: 'Solicitud de cambio de plan enviada. Te avisaremos cuando la revisemos.',
                code: 201
            );
        } catch (Exception $e) {
            $codigo = (int) ($e->getCode() ?: 400);

            return ApiResponse::error(
                message: $e->getMessage(),
                code: $codigo >= 400 && $codigo < 600 ? $codigo : 400
            );
        }
    }
}
