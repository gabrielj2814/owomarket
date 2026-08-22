<?php

declare(strict_types=1);

namespace Src\Admin\Application\UseCase;

use Exception;
use Illuminate\Support\Str;
use Src\Category\Infrastructure\Eloquent\Models\CentralCategory;

final class SaveMasterCategoryUseCase
{
    /**
     * @param array{
     *     id?: string|null,
     *     name: string,
     *     slug?: string|null,
     *     parent_id?: string|null,
     *     description?: string|null,
     *     icon?: string|null,
     *     image?: string|null,
     *     position?: int,
     *     is_active?: bool
     * } $data
     */
    public function execute(array $data): CentralCategory
    {
        $id = $data['id'] ?? null;
        $name = trim($data['name']);
        $slug = ! empty($data['slug']) ? Str::slug($data['slug']) : Str::slug($name);

        if ($id) {
            $cat = CentralCategory::find($id);
            if (! $cat) {
                throw new Exception("Categoría central '{$id}' no encontrada.", 404);
            }
        } else {
            $cat = new CentralCategory;
            $cat->id = (string) Str::uuid();
        }

        $cat->name = $name;
        $cat->slug = $slug;
        $cat->parent_id = ! empty($data['parent_id']) ? $data['parent_id'] : null;
        $cat->description = $data['description'] ?? $cat->description;
        $cat->icon = $data['icon'] ?? $cat->icon;
        $cat->image = $data['image'] ?? $cat->image;
        $cat->position = isset($data['position']) ? (int) $data['position'] : ($cat->position ?? 0);
        $cat->is_active = isset($data['is_active']) ? (bool) $data['is_active'] : ($cat->is_active ?? true);

        $cat->save();

        return $cat;
    }
}
