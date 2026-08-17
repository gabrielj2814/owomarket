<?php

declare(strict_types=1);

namespace Src\Attribute\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Src\Attribute\Application\UseCase\ListAttributesWithValuesUseCase;
use Src\Shared\Helper\ApiResponse;

final class ListAttributesWithValuesGETController
{
    public function __construct(
        private readonly ListAttributesWithValuesUseCase $useCase
    ) {}

    public function __invoke(): JsonResponse
    {
        try {
            $attributes = $this->useCase->execute();
            $data = array_map(fn ($attr) => $attr->toArray(), $attributes);

            return ApiResponse::success(
                data: $data,
                message: 'Atributos con valores listados exitosamente',
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
