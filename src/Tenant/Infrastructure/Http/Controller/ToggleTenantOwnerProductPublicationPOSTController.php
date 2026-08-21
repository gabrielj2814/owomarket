<?php

declare(strict_types=1);

namespace Src\Tenant\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Shared\Helper\ApiResponse;
use Src\Tenant\Application\UseCase\ToggleProductMarketplacePublicationUseCase;

final class ToggleTenantOwnerProductPublicationPOSTController
{
    public function __construct(
        private readonly ToggleProductMarketplacePublicationUseCase $useCase
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        // La identidad SIEMPRE sale de la sesión (hallazgo A2).
        $userId = (string) (auth()->id() ?? '');

        if ($userId === '') {
            return ApiResponse::error('Debes iniciar sesión para publicar productos.', 401);
        }

        $status = $request->has('status') ? (bool) $request->input('status') : null;

        try {
            $result = $this->useCase->execute($userId, $id, $status);

            return ApiResponse::success(
                data: $result,
                message: $result['message'],
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
