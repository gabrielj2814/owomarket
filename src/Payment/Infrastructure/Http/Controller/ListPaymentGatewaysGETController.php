<?php

declare(strict_types=1);

namespace Src\Payment\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Src\Payment\Application\UseCases\ListAvailablePaymentGatewaysUseCase;
use Src\Shared\Helper\ApiResponse;

final class ListPaymentGatewaysGETController extends Controller
{
    public function __construct(
        private readonly ListAvailablePaymentGatewaysUseCase $useCase
    ) {}

    public function __invoke(): JsonResponse
    {
        $gateways = $this->useCase->execute();

        return ApiResponse::success(
            data: array_map(fn ($g) => $g->toArray(), $gateways),
            message: 'Métodos de pago consultados exitosamente'
        );
    }
}
