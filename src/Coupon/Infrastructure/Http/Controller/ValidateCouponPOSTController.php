<?php

declare(strict_types=1);

namespace Src\Coupon\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Src\Coupon\Application\UseCase\ValidateCouponUseCase;
use Src\Coupon\Infrastructure\Http\Request\ValidateCouponFormRequest;
use Src\Shared\Helper\ApiResponse;

final class ValidateCouponPOSTController
{
    public function __construct(
        private readonly ValidateCouponUseCase $useCase
    ) {}

    public function __invoke(ValidateCouponFormRequest $request): JsonResponse
    {
        try {
            $result = $this->useCase->execute(
                code: (string) $request->input('code'),
                orderSubtotal: (float) $request->input('order_subtotal'),
                currentDate: (string) $request->input('current_date', 'now')
            );

            if (! $result->isValid) {
                return ApiResponse::error(
                    message: $result->message,
                    code: 400,
                    errors: ['code' => [$result->message]]
                );
            }

            return ApiResponse::success(
                data: $result->toArray(),
                message: $result->message,
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
