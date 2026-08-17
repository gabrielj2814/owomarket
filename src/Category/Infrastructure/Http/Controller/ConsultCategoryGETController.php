<?php

declare(strict_types=1);

namespace Src\Category\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Src\Category\Application\UseCase\ConsultCategoryByIdUseCase;
use Src\Shared\Helper\ApiResponse;

class ConsultCategoryGETController extends Controller
{
    public function __construct(
        protected ConsultCategoryByIdUseCase $consultCategoryByIdUseCase
    ) {}

    public function __invoke(int $id): JsonResponse
    {
        try {
            $category = $this->consultCategoryByIdUseCase->execute($id);

            return ApiResponse::success(
                data: $category->toArray(),
                message: 'Categoría obtenida exitosamente',
                code: 200
            );
        } catch (Exception $e) {
            $code = $e->getCode() >= 400 && $e->getCode() <= 500 ? $e->getCode() : 404;

            return ApiResponse::error(message: $e->getMessage(), code: $code);
        }
    }
}
