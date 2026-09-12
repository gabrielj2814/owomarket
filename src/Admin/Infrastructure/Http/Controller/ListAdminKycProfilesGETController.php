<?php

declare(strict_types=1);

namespace Src\Admin\Infrastructure\Http\Controller;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Admin\Application\UseCase\ListTenantKycProfilesUseCase;
use Src\Shared\Helper\ApiResponse;

/**
 * Los expedientes de identidad, para refrescar la lista sin recargar la página (subsistema 1).
 */
final class ListAdminKycProfilesGETController
{
    public function __construct(
        private readonly ListTenantKycProfilesUseCase $useCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $resultado = $this->useCase->execute([
            'status' => $request->query('status'),
            'search' => $request->query('search'),
            'page' => (int) $request->query('page', 1),
            'per_page' => (int) $request->query('per_page', 15),
        ]);

        return ApiResponse::success(
            data: $resultado,
            message: 'Expedientes de verificación recuperados correctamente.'
        );
    }
}
