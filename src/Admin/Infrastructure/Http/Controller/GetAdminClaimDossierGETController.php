<?php

declare(strict_types=1);

namespace Src\Admin\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Src\Admin\Application\UseCase\BuildClaimDossierUseCase;
use Src\Shared\Helper\ApiResponse;

/**
 * El expediente completo de una reclamación (subsistema 5, fase D).
 *
 * Va aparte del listado a propósito: el listado se consulta muchas veces y no lleva ningún dato
 * de identidad, y el expediente es una petición deliberada. Esa separación es lo que hace que
 * el dato sensible viaje sólo cuando alguien de verdad va a usarlo.
 */
final class GetAdminClaimDossierGETController
{
    public function __construct(
        private readonly BuildClaimDossierUseCase $useCase
    ) {}

    public function __invoke(string $claimId): JsonResponse
    {
        try {
            return ApiResponse::success(
                data: $this->useCase->execute($claimId),
                message: 'Expediente de reclamación recuperado.'
            );
        } catch (Exception $e) {
            $codigo = (int) $e->getCode();

            return ApiResponse::error(
                message: $e->getMessage(),
                code: $codigo >= 400 && $codigo < 600 ? $codigo : 400
            );
        }
    }
}
