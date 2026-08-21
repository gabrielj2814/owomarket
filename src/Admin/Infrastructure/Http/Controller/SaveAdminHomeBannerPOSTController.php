<?php

declare(strict_types=1);

namespace Src\Admin\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Admin\Application\UseCase\SaveHomeBannerUseCase;
use Src\Shared\Helper\ApiResponse;

final class SaveAdminHomeBannerPOSTController
{
    public function __construct(
        private readonly SaveHomeBannerUseCase $useCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'nullable|string|uuid',
            'title' => 'required|string|max:150',
            'subtitle' => 'nullable|string|max:255',
            'image_url' => 'required|string|max:500',
            'link_url' => 'nullable|string|max:500',
            'badge_text' => 'nullable|string|max:80',
            'position_type' => 'nullable|string|in:hero_slider,top_promo,featured_grid,footer_banner',
            'order_position' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        try {
            $banner = $this->useCase->execute([
                'id' => $request->input('id'),
                'title' => $request->input('title'),
                'subtitle' => $request->input('subtitle'),
                'image_url' => $request->input('image_url'),
                'link_url' => $request->input('link_url'),
                'badge_text' => $request->input('badge_text'),
                'position_type' => $request->input('position_type', 'hero_slider'),
                'order_position' => (int) $request->input('order_position', 0),
                'is_active' => $request->input('is_active', true),
                'start_date' => $request->input('start_date'),
                'end_date' => $request->input('end_date'),
            ]);

            return ApiResponse::success(
                data: $banner,
                message: 'Banner guardado exitosamente.'
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                code: 400
            );
        }
    }
}
