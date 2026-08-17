<?php

declare(strict_types=1);

namespace Src\Coupon\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Src\Coupon\Application\UseCase\ConsultCouponByIdUseCase;
use Src\Shared\Helper\ApiResponse;

final class ConsultCouponGETController
{
    public function __construct(
        private readonly ConsultCouponByIdUseCase $useCase
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        try {
            $coupon = $this->useCase->execute($id);

            return ApiResponse::success(
                data: $coupon->toArray(),
                message: 'Cupón consultado exitosamente',
                code: 200
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                code: (int) ($e->getCode() ?: 404)
            );
        }
    }
}
