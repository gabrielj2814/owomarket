<?php

declare(strict_types=1);

namespace Src\Admin\Application\UseCase;

use Src\Admin\Infrastructure\Eloquent\Models\CentralHomeBanner;
use Exception;
use Illuminate\Support\Str;

final class SaveHomeBannerUseCase
{
    /**
     * @param array{
     *     id?: string|null,
     *     title: string,
     *     subtitle?: string|null,
     *     image_url: string,
     *     link_url?: string|null,
     *     badge_text?: string|null,
     *     position_type?: 'hero_slider'|'top_promo'|'featured_grid'|'footer_banner',
     *     order_position?: int,
     *     is_active?: bool,
     *     start_date?: string|null,
     *     end_date?: string|null
     * } $data
     */
    public function execute(array $data): CentralHomeBanner
    {
        $id = $data['id'] ?? null;

        if ($id) {
            $banner = CentralHomeBanner::find($id);
            if (! $banner) {
                throw new Exception("Banner '{$id}' no encontrado.", 404);
            }
        } else {
            $banner = new CentralHomeBanner();
            $banner->id = (string) Str::uuid();
        }

        $banner->title = trim($data['title']);
        $banner->subtitle = $data['subtitle'] ?? $banner->subtitle;
        $banner->image_url = $data['image_url'];
        $banner->link_url = $data['link_url'] ?? $banner->link_url;
        $banner->badge_text = $data['badge_text'] ?? $banner->badge_text;
        $banner->position_type = $data['position_type'] ?? ($banner->position_type ?? 'hero_slider');
        $banner->order_position = isset($data['order_position']) ? (int) $data['order_position'] : ($banner->order_position ?? 0);
        $banner->is_active = isset($data['is_active']) ? (bool) $data['is_active'] : ($banner->is_active ?? true);
        $banner->start_date = ! empty($data['start_date']) ? $data['start_date'] : null;
        $banner->end_date = ! empty($data['end_date']) ? $data['end_date'] : null;

        $banner->save();

        return $banner;
    }
}
