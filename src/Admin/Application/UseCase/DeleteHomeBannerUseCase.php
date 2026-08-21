<?php

declare(strict_types=1);

namespace Src\Admin\Application\UseCase;

use Src\Admin\Infrastructure\Eloquent\Models\CentralHomeBanner;
use Exception;

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
