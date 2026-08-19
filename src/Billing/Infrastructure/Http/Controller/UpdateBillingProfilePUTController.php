<?php

declare(strict_types=1);

namespace Src\Billing\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Src\Billing\Application\UseCases\UpdateBillingProfileUseCase;
use Src\Billing\Infrastructure\Http\Request\UpdateBillingProfileFormRequest;
use Src\Shared\Helper\ApiResponse;

final class UpdateBillingProfilePUTController extends Controller
{
    public function __construct(
        private readonly UpdateBillingProfileUseCase $useCase
    ) {}

    public function __invoke(UpdateBillingProfileFormRequest $request): JsonResponse
    {
        $dto = $request->toDto();
        $profile = $this->useCase->execute($dto);

        return ApiResponse::success(
            data: $profile->toArray(),
            message: 'Perfil fiscal actualizado exitosamente'
        );
    }
}
