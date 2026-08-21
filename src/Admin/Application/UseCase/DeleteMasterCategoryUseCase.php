<?php

declare(strict_types=1);

namespace Src\Admin\Application\UseCase;

use Src\Category\Infrastructure\Eloquent\Models\CentralCategory;
use Exception;

final class DeleteMasterCategoryUseCase
{
    public function execute(string $id): bool
    {
        $cat = CentralCategory::find($id);

        if (! $cat) {
            throw new Exception("Categoría central '{$id}' no encontrada.", 404);
        }

        // Si tiene hijos, desacoplarlos o eliminarlos
        CentralCategory::where('parent_id', $id)->update(['parent_id' => null]);

        return (bool) $cat->delete();
    }
}
