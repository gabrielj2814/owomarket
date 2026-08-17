<?php

declare(strict_types=1);

namespace Src\Coupon\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Src\Coupon\Application\UseCase\DeleteCouponUseCase;
use Src\Shared\Helper\ApiResponse;

final class DeleteCouponDELETEController
{
    public function __construct(
        private readonly DeleteCouponUseCase $useCase
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        try {
            $this->useCase->execute($id);

            return ApiResponse::success(
                data: null,
                message: 'Cupón eliminado exitosamente',
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
