<?php

declare(strict_types=1);

namespace Src\Brand\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Src\Brand\Application\UseCase\EditBrandUseCase;
use Src\Brand\Infrastructure\Http\Request\EditBrandFormRequest;
use Src\Shared\Helper\ApiResponse;

final class EditBrandPUTController
{
    public function __construct(
        private readonly EditBrandUseCase $useCase
    ) {}

    public function __invoke(int $id, EditBrandFormRequest $request): JsonResponse
    {
        try {
            $brand = $this->useCase->execute(
                id: $id,
                name: (string) $request->input('name'),
                slug: $request->filled('slug') ? (string) $request->input('slug') : null,
                description: $request->input('description'),
                logo: $request->input('logo'),
                isActive: (bool) $request->input('is_active', true),
                position: (int) $request->input('position', 0)
            );

            return ApiResponse::success(
                data: $brand->toArray(),
                message: 'Marca actualizada exitosamente',
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
