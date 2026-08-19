<?php

declare(strict_types=1);

namespace Src\Brand\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Src\Brand\Application\UseCase\DeleteBrandUseCase;
use Src\Shared\Helper\ApiResponse;

final class DeleteBrandDELETEController
{
    public function __construct(
        private readonly DeleteBrandUseCase $useCase
    ) {}

    public function __invoke(int $id): JsonResponse
    {
        try {
            $this->useCase->execute($id);

            return ApiResponse::success(
                data: null,
                message: 'Marca eliminada exitosamente',
                code: 200
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                code: (int) ($e->getCode() ?: 404)
            );
        }
    }
}
