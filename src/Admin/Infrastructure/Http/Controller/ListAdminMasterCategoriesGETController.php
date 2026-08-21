<?php

declare(strict_types=1);

namespace Src\Admin\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Src\Admin\Application\UseCase\ListMasterCategoriesUseCase;
use Src\Shared\Helper\ApiResponse;

final class ListAdminMasterCategoriesGETController
{
    public function __construct(
        private readonly ListMasterCategoriesUseCase $useCase
    ) {}

    public function __invoke(): JsonResponse
    {
        try {
            $result = $this->useCase->execute();

            return ApiResponse::success(
                data: $result,
                message: 'Categorías maestras obtenidas exitosamente.'
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                code: 400
            );
        }
    }
}
