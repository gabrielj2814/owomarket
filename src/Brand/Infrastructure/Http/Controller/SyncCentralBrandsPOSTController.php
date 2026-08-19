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

        $message = match (true) {
            $result['created_count'] > 0 && $result['updated_count'] > 0 =>
                "Marcas sincronizadas: {$result['created_count']} creadas, {$result['updated_count']} actualizadas.",
            $result['created_count'] > 0 =>
                "Marcas sincronizadas: {$result['created_count']} nuevas marcas agregadas.",
            $result['updated_count'] > 0 =>
                "Marcas sincronizadas: {$result['updated_count']} registros actualizados con la base central.",
            default =>
                "El catálogo de marcas ya se encuentra al día con la base central ({$result['unchanged_count']} verificadas).",
        };

        return ApiResponse::success(
            data: $result,
            message: $message
        );
    }
}
