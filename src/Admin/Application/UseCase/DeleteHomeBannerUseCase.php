<?php

declare(strict_types=1);

namespace Src\Admin\Application\UseCase;

use Exception;
use Src\Admin\Infrastructure\Eloquent\Models\CentralHomeBanner;

final class DeleteHomeBannerUseCase
{
    public function execute(string $id): bool
    {
        $banner = CentralHomeBanner::find($id);

        if (! $banner) {
            throw new Exception("Banner '{$id}' no encontrado.", 404);
        }

        return (bool) $banner->delete();
    }
}
