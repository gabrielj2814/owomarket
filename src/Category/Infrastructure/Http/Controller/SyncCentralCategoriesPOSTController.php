<?php

declare(strict_types=1);

namespace Src\Category\Infrastructure\Http\Controller;

use Illuminate\Http\JsonResponse;
use Src\Category\Application\UseCase\SyncCentralCategoriesUseCase;
use Src\Shared\Helper\ApiResponse;

final class SyncCentralCategoriesPOSTController
{
    public function __construct(
        private readonly SyncCentralCategoriesUseCase $useCase
    ) {}

    public function __invoke(): JsonResponse
    {
        $result = $this->useCase->execute();

        return ApiResponse::success(
            data: $result,
            message: "Categorías maestras sincronizadas correctamente ({$result['synced_count']} procesadas)"
        );
    }
}
