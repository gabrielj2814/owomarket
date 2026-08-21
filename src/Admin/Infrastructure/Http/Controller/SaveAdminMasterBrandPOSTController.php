<?php

declare(strict_types=1);

namespace Src\Admin\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Admin\Application\UseCase\SaveMasterBrandUseCase;
use Src\Shared\Helper\ApiResponse;

final class SaveAdminMasterBrandPOSTController
{
    public function __construct(
        private readonly SaveMasterBrandUseCase $useCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'nullable|string|uuid',
            'name' => 'required|string|max:150',
            'slug' => 'nullable|string|max:150',
            'logo' => 'nullable|string|max:500',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
            'position' => 'nullable|integer',
        ]);

        try {
            $brand = $this->useCase->execute([
                'id' => $request->input('id'),
                'name' => $request->input('name'),
                'slug' => $request->input('slug'),
                'logo' => $request->input('logo'),
                'description' => $request->input('description'),
                'is_active' => $request->input('is_active', true),
                'position' => (int) $request->input('position', 0),
            ]);

            return ApiResponse::success(
                data: $brand,
                message: 'Marca maestra guardada exitosamente.'
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                code: 400
            );
        }
    }
}
