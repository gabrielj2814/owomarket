<?php

declare(strict_types=1);

namespace Src\Attribute\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Src\Attribute\Application\UseCase\ConsultAttributeByIdUseCase;
use Src\Shared\Helper\ApiResponse;

final class ConsultAttributeGETController
{
    public function __construct(
        private readonly ConsultAttributeByIdUseCase $useCase
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        try {
            $attribute = $this->useCase->execute($id);

            return ApiResponse::success(
                data: $attribute->toArray(),
                message: 'Atributo consultado exitosamente',
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
