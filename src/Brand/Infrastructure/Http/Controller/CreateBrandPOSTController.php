<?php

declare(strict_types=1);

namespace Src\Brand\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Src\Brand\Application\UseCase\CreateBrandUseCase;
use Src\Brand\Infrastructure\Http\Request\CreateBrandFormRequest;
use Src\Shared\Helper\ApiResponse;

final class CreateBrandPOSTController
{
    public function __construct(
        private readonly CreateBrandUseCase $useCase
    ) {}

    public function __invoke(CreateBrandFormRequest $request): JsonResponse
    {
        try {
            $brand = $this->useCase->execute(
                name: (string) $request->input('name'),
                slug: $request->filled('slug') ? (string) $request->input('slug') : null,
                description: $request->input('description'),
                logo: $request->input('logo'),
                isActive: (bool) $request->input('is_active', true),
                position: (int) $request->input('position', 0)
            );

            return ApiResponse::success(
                data: $brand->toArray(),
                message: 'Marca creada exitosamente',
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
