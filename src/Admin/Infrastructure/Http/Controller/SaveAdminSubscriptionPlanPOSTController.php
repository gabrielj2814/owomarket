<?php

declare(strict_types=1);

namespace Src\Admin\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Admin\Application\UseCase\SaveSubscriptionPlanUseCase;
use Src\Shared\Helper\ApiResponse;

final class SaveAdminSubscriptionPlanPOSTController
{
    public function __construct(
        private readonly SaveSubscriptionPlanUseCase $useCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'nullable|string|uuid',
            'name' => 'required|string|max:120',
            'slug' => 'nullable|string|max:120',
            'description' => 'nullable|string|max:1000',
            'price_monthly' => 'required|numeric|min:0',
            'price_yearly' => 'nullable|numeric|min:0',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'max_products' => 'required|integer|min:1',
            'features' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        try {
            $plan = $this->useCase->execute([
                'id' => $request->input('id'),
                'name' => $request->input('name'),
                'slug' => $request->input('slug'),
                'description' => $request->input('description'),
                'price_monthly' => (float) $request->input('price_monthly'),
                'price_yearly' => $request->input('price_yearly') ? (float) $request->input('price_yearly') : null,
                'commission_rate' => (float) $request->input('commission_rate'),
                'max_products' => (int) $request->input('max_products'),
                'features' => $request->input('features', []),
                'is_active' => $request->input('is_active', true),
            ]);

            return ApiResponse::success(
                data: $plan,
                message: 'Plan de suscripción guardado exitosamente.'
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                code: 400
            );
        }
    }
}
