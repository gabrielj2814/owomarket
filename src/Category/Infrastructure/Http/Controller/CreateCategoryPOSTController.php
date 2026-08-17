<?php

declare(strict_types=1);

namespace Src\Category\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Src\Category\Application\UseCase\CreateCategoryUseCase;
use Src\Category\Infrastructure\Http\Request\CreateCategoryFormRequest;
use Src\Shared\Helper\ApiResponse;

class CreateCategoryPOSTController extends Controller
{
    public function __construct(
        protected CreateCategoryUseCase $createCategoryUseCase
    ) {}

    public function __invoke(CreateCategoryFormRequest $request): JsonResponse
    {
        try {
            $category = $this->createCategoryUseCase->execute(
                name: (string) $request->input('name'),
                slug: $request->input('slug') ? (string) $request->input('slug') : null,
                description: $request->input('description') ? (string) $request->input('description') : null,
                image: $request->input('image') ? (string) $request->input('image') : null,
                parentId: $request->filled('parent_id') ? (int) $request->input('parent_id') : null,
                isActive: $request->has('is_active') ? (bool) $request->input('is_active') : true,
                position: (int) $request->input('position', 0)
            );

            return ApiResponse::success(
                data: $category->toArray(),
                message: 'Categoría creada exitosamente',
                code: 201
            );
        } catch (Exception $e) {
            Log::error('Error creating category: '.$e->getMessage(), ['exception' => $e]);
            $code = $e->getCode() >= 400 && $e->getCode() <= 500 ? $e->getCode() : 400;

            return ApiResponse::error(message: $e->getMessage(), code: $code);
        }
    }
}
