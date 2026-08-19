<?php

declare(strict_types=1);

namespace Src\Attribute\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Src\Attribute\Application\DTOs\AttributeValueData;
use Src\Attribute\Application\UseCase\CreateAttributeUseCase;
use Src\Attribute\Infrastructure\Http\Request\CreateAttributeFormRequest;
use Src\Shared\Helper\ApiResponse;

final class CreateAttributePOSTController
{
    public function __construct(
        private readonly CreateAttributeUseCase $useCase
    ) {}

    public function __invoke(CreateAttributeFormRequest $request): JsonResponse
    {
        try {
            $valuesData = [];
            if ($request->has('values') && is_array($request->input('values'))) {
                foreach ($request->input('values') as $val) {
                    if (isset($val['value']) && trim($val['value']) !== '') {
                        $valuesData[] = new AttributeValueData(
                            value: (string) $val['value'],
                            color: $val['color'] ?? null,
                            image: $val['image'] ?? null,
                            position: (int) ($val['position'] ?? 0)
                        );
                    }
                }
            }

            $attribute = $this->useCase->execute(
                name: (string) $request->input('name'),
                slug: $request->filled('slug') ? (string) $request->input('slug') : null,
                type: (string) $request->input('type', 'select'),
                isFilterable: (bool) $request->input('is_filterable', false),
                isVisible: (bool) $request->input('is_visible', true),
                position: (int) $request->input('position', 0),
                values: $valuesData
            );

            return ApiResponse::success(
                data: $attribute->toArray(),
                message: 'Atributo creado exitosamente',
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
