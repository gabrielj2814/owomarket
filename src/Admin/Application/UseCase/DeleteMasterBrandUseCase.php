<?php

declare(strict_types=1);

namespace Src\Admin\Application\UseCase;

use Src\Brand\Infrastructure\Eloquent\Models\CentralBrand;
use Exception;

final class DeleteMasterBrandUseCase
{
    public function execute(string $id): bool
    {
        $brand = CentralBrand::find($id);

        if (! $brand) {
            throw new Exception("Marca central '{$id}' no encontrada.", 404);
        }

        return (bool) $brand->delete();
    }
}
