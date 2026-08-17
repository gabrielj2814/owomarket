<?php

declare(strict_types=1);

namespace Src\Attribute\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Src\Attribute\Application\UseCase\CreateAttributeValueUseCase;
use Src\Attribute\Infrastructure\Http\Request\CreateAttributeValueFormRequest;
use Src\Shared\Helper\ApiResponse;

final class CreateAttributeValuePOSTController
{
    public function __construct(
        private readonly CreateAttributeValueUseCase $useCase
    ) {}

    public function __invoke(string $attributeId, CreateAttributeValueFormRequest $request): JsonResponse
    {
        try {
            $attributeValue = $this->useCase->execute(
                attributeId: $attributeId,
                value: (string) $request->input('value'),
                color: $request->filled('color') ? (string) $request->input('color') : null,
                image: $request->filled('image') ? (string) $request->input('image') : null,
                position: (int) $request->input('position', 0)
            );

            return ApiResponse::success(
                data: $attributeValue->toArray(),
                message: 'Valor de atributo creado exitosamente',
                code: 201
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                code: (int) ($e->getCode() ?: 400)
            );
        }
    }
}
