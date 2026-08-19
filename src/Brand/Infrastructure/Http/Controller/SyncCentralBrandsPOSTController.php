<?php

declare(strict_types=1);

namespace Src\Brand\Infrastructure\Http\Controller;

use Illuminate\Http\JsonResponse;
use Src\Brand\Application\UseCase\SyncCentralBrandsUseCase;
use Src\Shared\Helper\ApiResponse;

final class SyncCentralBrandsPOSTController
{
    public function __construct(
        private readonly SyncCentralBrandsUseCase $useCase
    ) {}

    public function __invoke(): JsonResponse
    {
        $result = $this->useCase->execute();

        return ApiResponse::success(
            data: $result,
            message: "Marcas maestras sincronizadas correctamente ({$result['synced_count']} procesadas)"
        );
    }
}
