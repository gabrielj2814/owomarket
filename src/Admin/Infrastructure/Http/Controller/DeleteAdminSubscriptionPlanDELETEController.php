<?php

declare(strict_types=1);

namespace Src\Admin\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Src\Admin\Application\UseCase\DeleteSubscriptionPlanUseCase;
use Src\Shared\Helper\ApiResponse;

final class DeleteAdminSubscriptionPlanDELETEController
{
    public function __construct(
        private readonly DeleteSubscriptionPlanUseCase $useCase
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        try {
            $this->useCase->execute($id);

            return ApiResponse::success(
                data: null,
                message: 'Plan de suscripción eliminado exitosamente.'
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                code: 400
            );
        }
    }
}
