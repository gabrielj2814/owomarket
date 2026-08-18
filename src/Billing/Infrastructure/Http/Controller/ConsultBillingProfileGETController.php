<?php

declare(strict_types=1);

namespace Src\Billing\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Src\Billing\Application\UseCases\ConsultBillingProfileUseCase;
use Src\Shared\Helper\ApiResponse;

final class ConsultBillingProfileGETController extends Controller
{
    public function __construct(
        private readonly ConsultBillingProfileUseCase $useCase
    ) {}

    public function __invoke(): JsonResponse
    {
        $profile = $this->useCase->execute();

        return ApiResponse::success(
            data: $profile?->toArray(),
            message: $profile ? 'Perfil fiscal consultado exitosamente' : 'No se ha configurado el perfil fiscal aún'
        );
    }
}
