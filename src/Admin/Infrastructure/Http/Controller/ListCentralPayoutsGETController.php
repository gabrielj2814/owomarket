<?php

declare(strict_types=1);

namespace Src\Admin\Infrastructure\Http\Controller;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Admin\Application\UseCase\ListCentralPayoutRequestsUseCase;
use Src\Shared\Helper\ApiResponse;

final class ListCentralPayoutsGETController
{
    public function __construct(
        private readonly ListCentralPayoutRequestsUseCase $useCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $filters = [
            'status' => $request->query('status'),
            'payment_method' => $request->query('payment_method'),
            'search' => $request->query('search'),
            'page' => (int) $request->query('page', 1),
            'per_page' => (int) $request->query('per_page', 15),
        ];

        $data = $this->useCase->execute($filters);

        return ApiResponse::success(
            data: $data,
            message: 'Solicitudes de retiro recuperadas exitosamente.'
        );
    }
}
