<?php

declare(strict_types=1);

namespace Src\Admin\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Admin\Application\UseCase\SaveMasterCategoryUseCase;
use Src\Shared\Helper\ApiResponse;

final class SaveAdminMasterCategoryPOSTController
{
    public function __construct(
        private readonly SaveMasterCategoryUseCase $useCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'nullable|string|uuid',
            'name' => 'required|string|max:150',
            'slug' => 'nullable|string|max:150',
            'parent_id' => 'nullable|string|uuid',
            'description' => 'nullable|string|max:1000',
            'icon' => 'nullable|string|max:255',
            'image' => 'nullable|string|max:500',
            'position' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        try {
            $cat = $this->useCase->execute([
                'id' => $request->input('id'),
                'name' => $request->input('name'),
                'slug' => $request->input('slug'),
                'parent_id' => $request->input('parent_id'),
                'description' => $request->input('description'),
                'icon' => $request->input('icon'),
                'image' => $request->input('image'),
                'position' => (int) $request->input('position', 0),
                'is_active' => $request->input('is_active', true),
            ]);

            return ApiResponse::success(
                data: $cat,
                message: 'Categoría maestra guardada exitosamente.'
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                code: 400
            );
        }
    }
}
