<?php

declare(strict_types=1);

namespace Src\Admin\Infrastructure\Http\Controller;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Admin\Application\UseCase\ListAdminClaimsUseCase;
use Src\Shared\Helper\ApiResponse;

/**
 * Las reclamaciones de la plataforma, para refrescar la lista sin recargar (subsistema 5).
 */
final class ListAdminClaimsGETController
{
    public function __construct(
        private readonly ListAdminClaimsUseCase $useCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $resultado = $this->useCase->execute([
            'status' => $request->query('status'),
            'search' => $request->query('search'),
            'page' => (int) $request->query('page', 1),
            'per_page' => (int) $request->query('per_page', 15),
        ]);

        return ApiResponse::success($resultado, 'Reclamaciones recuperadas correctamente.');
    }
}
