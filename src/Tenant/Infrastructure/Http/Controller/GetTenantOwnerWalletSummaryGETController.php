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
        $userId = (string) ($request->query('user_id') 
            ?: $request->input('user_id') 
            ?: $request->header('X-User-Id') 
            ?: auth()->id());

        if (empty($userId)) {
            return ApiResponse::error('El parámetro user_id es obligatorio', 400);
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
