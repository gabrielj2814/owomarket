<?php

declare(strict_types=1);

namespace Src\Admin\Application\UseCase;

use App\Models\CentralHomeBanner;
use Illuminate\Database\Eloquent\Collection;

final class ListHomeBannersUseCase
{
    /**
     * @return array{
     *     banners: Collection,
     *     metrics: array{
     *         total_banners: int,
     *         active_banners: int,
     *         hero_sliders: int
     *     }
     * }
     */
    public function execute(): array
    {
        $banners = CentralHomeBanner::orderBy('order_position', 'asc')->orderBy('created_at', 'desc')->get();

        $total = CentralHomeBanner::count();
        $active = CentralHomeBanner::where('is_active', true)->count();
        $hero = CentralHomeBanner::where('position_type', 'hero_slider')->count();

        return [
            'banners' => $banners,
            'metrics' => [
                'total_banners' => $total,
                'active_banners' => $active,
                'hero_sliders' => $hero,
            ],
        ];
    }
}
