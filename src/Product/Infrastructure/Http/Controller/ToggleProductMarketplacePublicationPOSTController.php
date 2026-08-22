<?php

declare(strict_types=1);

namespace Src\Product\Infrastructure\Http\Controller;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Product\Application\UseCase\ToggleProductMarketplacePublicationUseCase;
use Src\Shared\Helper\ApiResponse;

final class ToggleProductMarketplacePublicationPOSTController
{
    public function __construct(
        private readonly ToggleProductMarketplacePublicationUseCase $useCase
    ) {}

    public function __invoke(string $id, Request $request): JsonResponse
    {
        try {
            $isPublished = $request->has('is_published_central')
                ? (bool) $request->input('is_published_central')
                : null;

            $product = $this->useCase->execute($id, $isPublished);

            $statusText = $product->is_published_central ? 'publicado en' : 'retirado de';

            return ApiResponse::success(
                data: $product,
                message: "Producto {$statusText} el Marketplace Central exitosamente"
            );
        } catch (\Throwable $e) {
            $code = $e->getCode();
            $httpCode = is_int($code) && $code >= 100 && $code <= 599 ? $code : 400;

            return ApiResponse::error(
                message: $e->getMessage(),
                code: $httpCode
            );
        }
    }
}
