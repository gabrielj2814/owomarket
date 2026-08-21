<?php

declare(strict_types=1);

namespace Src\Admin\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Src\Admin\Application\UseCase\ListHomeBannersUseCase;
use Src\Shared\Helper\ApiResponse;

final class ListAdminHomeBannersGETController
{
    public function __construct(
        private readonly ListHomeBannersUseCase $useCase
    ) {}

    public function __invoke(): JsonResponse
    {
        try {
            $result = $this->useCase->execute();

            return ApiResponse::success(
                data: $result,
                message: 'Banners de la home obtenidos exitosamente.'
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                code: 400
            );
        }
    }
}
