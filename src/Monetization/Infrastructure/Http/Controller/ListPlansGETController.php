<?php

declare(strict_types=1);

namespace Src\Monetization\Infrastructure\Http\Controller;

use Illuminate\Http\JsonResponse;
use Src\Monetization\Application\UseCases\ListSubscriptionPlansUseCase;
use Src\Shared\Helper\ApiResponse;

final class ListPlansGETController
{
    public function __construct(
        private readonly ListSubscriptionPlansUseCase $useCase
    ) {}

    public function __invoke(): JsonResponse
    {
        $plans = $this->useCase->execute();

        return ApiResponse::success(
            data: $plans,
            message: 'Planes de suscripción recuperados exitosamente'
        );
    }
}
