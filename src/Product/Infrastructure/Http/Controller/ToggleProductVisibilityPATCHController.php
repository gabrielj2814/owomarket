<?php

declare(strict_types=1);

namespace Src\Product\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Src\Product\Application\UseCase\ToggleProductVisibilityUseCase;
use Src\Product\Infrastructure\Http\Request\ToggleProductVisibilityFormRequest;
use Src\Shared\Helper\ApiResponse;

final class ToggleProductVisibilityPATCHController
{
    public function __construct(
        private readonly ToggleProductVisibilityUseCase $useCase
    ) {}

    public function __invoke(string $id, ToggleProductVisibilityFormRequest $request): JsonResponse
    {
        try {
            $isVisible = $request->has('is_visible') ? (bool) $request->input('is_visible') : null;

            $this->useCase->execute($id, $isVisible);

            return ApiResponse::success(
                data: null,
                message: 'Visibilidad del producto actualizada exitosamente',
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
