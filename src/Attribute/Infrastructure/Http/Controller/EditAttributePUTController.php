<?php

declare(strict_types=1);

namespace Src\Attribute\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Src\Attribute\Application\UseCase\EditAttributeUseCase;
use Src\Attribute\Infrastructure\Http\Request\EditAttributeFormRequest;
use Src\Shared\Helper\ApiResponse;

final class EditAttributePUTController
{
    public function __construct(
        private readonly EditAttributeUseCase $useCase
    ) {}

    public function __invoke(string $id, EditAttributeFormRequest $request): JsonResponse
    {
        try {
            $attribute = $this->useCase->execute(
                id: $id,
                name: (string) $request->input('name'),
                slug: $request->filled('slug') ? (string) $request->input('slug') : null,
                type: (string) $request->input('type', 'select'),
                isFilterable: (bool) $request->input('is_filterable', false),
                isVisible: (bool) $request->input('is_visible', true),
                position: (int) $request->input('position', 0)
            );

            return ApiResponse::success(
                data: $attribute->toArray(),
                message: 'Atributo actualizado exitosamente',
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
