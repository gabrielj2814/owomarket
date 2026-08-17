<?php

declare(strict_types=1);

namespace Src\Category\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Src\Category\Application\UseCase\DeleteCategoryUseCase;
use Src\Shared\Helper\ApiResponse;

class DeleteCategoryDELETEController extends Controller
{
    public function __construct(
        protected DeleteCategoryUseCase $deleteCategoryUseCase
    ) {}

    public function __invoke(int $id): JsonResponse
    {
        try {
            $this->deleteCategoryUseCase->execute($id);

            return ApiResponse::success(
                data: null,
                message: 'Categoría eliminada exitosamente',
                code: 200
            );
        } catch (Exception $e) {
            $code = $e->getCode() >= 400 && $e->getCode() <= 500 ? $e->getCode() : 404;

            return ApiResponse::error(message: $e->getMessage(), code: $code);
        }
    }
}
