<?php

declare(strict_types=1);

namespace Src\Brand\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Src\Brand\Application\UseCase\ListAllActiveBrandsUseCase;
use Src\Shared\Helper\ApiResponse;

final class ListAllActiveBrandsGETController
{
    public function __construct(
        private readonly ListAllActiveBrandsUseCase $useCase
    ) {}

    public function __invoke(): JsonResponse
    {
        try {
            $brands = $this->useCase->execute();

            $data = array_map(fn ($brand) => $brand->toArray(), $brands);

            return ApiResponse::success(
                data: $data,
                message: 'Marcas activas listadas exitosamente'
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                code: (int) ($e->getCode() ?: 400)
            );
        }
    }
}
