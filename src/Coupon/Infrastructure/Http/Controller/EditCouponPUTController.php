<?php

declare(strict_types=1);

namespace Src\Coupon\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Src\Coupon\Application\UseCase\EditCouponUseCase;
use Src\Coupon\Infrastructure\Http\Request\EditCouponFormRequest;
use Src\Shared\Helper\ApiResponse;

final class EditCouponPUTController
{
    public function __construct(
        private readonly EditCouponUseCase $useCase
    ) {}

    public function __invoke(string $id, EditCouponFormRequest $request): JsonResponse
    {
        try {
            $coupon = $this->useCase->execute(
                id: $id,
                code: (string) $request->input('code'),
                type: (string) $request->input('type'),
                value: (float) $request->input('value'),
                validFrom: (string) $request->input('valid_from'),
                validTo: (string) $request->input('valid_to'),
                minOrderAmount: $request->filled('min_order_amount') ? (float) $request->input('min_order_amount') : null,
                usageLimit: $request->filled('usage_limit') ? (int) $request->input('usage_limit') : null,
                usageLimitPerCustomer: $request->filled('usage_limit_per_customer') ? (int) $request->input('usage_limit_per_customer') : null,
                isActive: (bool) $request->input('is_active', true),
                applicableCategories: $request->input('applicable_categories'),
                applicableProducts: $request->input('applicable_products')
            );

            return ApiResponse::success(
                data: $coupon->toArray(),
                message: 'Cupón actualizado exitosamente',
                code: 200
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                code: (int) ($e->getCode() ?: 400)
            );
        }
    }
}
