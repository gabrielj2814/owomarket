<?php

declare(strict_types=1);

namespace Src\Admin\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Src\Admin\Application\UseCase\ListSubscriptionPlansUseCase;
use Src\Shared\Helper\ApiResponse;

final class ListAdminSubscriptionPlansGETController
{
    public function __construct(
        private readonly ListSubscriptionPlansUseCase $useCase
    ) {}

    public function __invoke(): JsonResponse
    {
        try {
            $result = $this->useCase->execute();

            return ApiResponse::success(
                data: $result,
                message: 'Planes de suscripción obtenidos exitosamente.'
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                code: 400
            );
        }
    }
}
