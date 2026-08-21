<?php

declare(strict_types=1);

namespace Src\Admin\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Admin\Application\UseCase\ModerateCentralProductUseCase;
use Src\Shared\Helper\ApiResponse;

final class ModerateAdminProductPOSTController
{
    public function __construct(
        private readonly ModerateCentralProductUseCase $useCase
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'is_visible' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
            'moderation_notes' => 'nullable|string|max:1000',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        try {
            $user = auth()->user();
            $adminUserId = (string) ($user?->id ?? 'system_admin');

            $product = $this->useCase->execute($id, $adminUserId, [
                'is_visible' => $request->has('is_visible') ? (bool) $request->input('is_visible') : null,
                'is_featured' => $request->has('is_featured') ? (bool) $request->input('is_featured') : null,
                'moderation_notes' => $request->input('moderation_notes'),
                'commission_rate' => $request->input('commission_rate'),
            ]);

            return ApiResponse::success(
                data: $product,
                message: 'Estado de moderación del producto actualizado exitosamente.'
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                code: 400
            );
        }
    }
}
