<?php

declare(strict_types=1);

namespace Src\Tenant\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Shared\Helper\ApiResponse;
use Src\Tenant\Application\UseCase\GetTenantOwnerWalletSummaryUseCase;

final class GetTenantOwnerWalletSummaryGETController
{
    public function __construct(
        private readonly GetTenantOwnerWalletSummaryUseCase $useCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        // La identidad SIEMPRE sale de la sesión. Antes se aceptaba 'user_id' por query
        // string o por la cabecera X-User-Id, lo que permitía leer la facturación de
        // cualquier comerciante (hallazgo A2).
        $userId = (string) (auth()->id() ?? '');

        if ($userId === '') {
            return ApiResponse::error('Debes iniciar sesión para consultar tu billetera.', 401);
        }

        try {
            $summary = $this->useCase->execute($userId);

            return ApiResponse::success(
                data: $summary,
                message: 'Resumen de billetera obtenido correctamente',
                code: 200
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                code: (int) ($e->getCode() ?: 400)
            );
        }
    }
}
