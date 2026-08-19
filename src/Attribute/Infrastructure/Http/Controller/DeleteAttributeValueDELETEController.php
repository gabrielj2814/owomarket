<?php

declare(strict_types=1);

namespace Src\Attribute\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Src\Attribute\Application\UseCase\DeleteAttributeValueUseCase;
use Src\Shared\Helper\ApiResponse;

final class DeleteAttributeValueDELETEController
{
    public function __construct(
        private readonly DeleteAttributeValueUseCase $useCase
    ) {}

    public function __invoke(string $valueId): JsonResponse
    {
        try {
            $this->useCase->execute($valueId);

            return ApiResponse::success(
                data: null,
                message: 'Valor de atributo eliminado exitosamente',
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
