<?php

declare(strict_types=1);

namespace Src\Category\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Src\Category\Application\UseCase\ListCategoriesTreeUseCase;
use Src\Shared\Helper\ApiResponse;

class ListCategoriesTreeGETController extends Controller
{
    public function __construct(
        protected ListCategoriesTreeUseCase $listCategoriesTreeUseCase
    ) {}

    public function __invoke(): JsonResponse
    {
        try {
            $categories = $this->listCategoriesTreeUseCase->execute();

            $data = array_map(fn ($cat) => $cat->toArray(), $categories);

            return ApiResponse::success(
                data: $data,
                message: 'Árbol de categorías obtenido exitosamente',
                code: 200
            );
        } catch (Exception $e) {
            return ApiResponse::error(message: $e->getMessage(), code: 400);
        }
    }
}
