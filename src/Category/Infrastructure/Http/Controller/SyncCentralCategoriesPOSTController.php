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

        $message = match (true) {
            $result['created_count'] > 0 && $result['updated_count'] > 0 =>
                "Categorías sincronizadas: {$result['created_count']} creadas, {$result['updated_count']} actualizadas.",
            $result['created_count'] > 0 =>
                "Categorías sincronizadas: {$result['created_count']} nuevas categorías agregadas.",
            $result['updated_count'] > 0 =>
                "Categorías sincronizadas: {$result['updated_count']} registros actualizados con la base central.",
            default =>
                "El catálogo de categorías ya se encuentra al día con la base central ({$result['unchanged_count']} verificadas).",
        };

        return ApiResponse::success(
            data: $result,
            message: $message
        );
    }
}
