<?php

declare(strict_types=1);

namespace Src\Admin\Application\UseCase;

use Illuminate\Database\Eloquent\Collection;
use Src\Category\Infrastructure\Eloquent\Models\CentralCategory;

final class ListMasterCategoriesUseCase
{
    /**
     * @return array{
     *     tree: array<mixed>,
     *     categories: Collection,
     *     metrics: array{
     *         total_categories: int,
     *         active_categories: int,
     *         root_categories: int
     *     }
     * }
     */
    public function execute(): array
    {
        $categories = CentralCategory::with('children')->orderBy('position', 'asc')->orderBy('name', 'asc')->get();

        $tree = $categories->whereNull('parent_id')->values()->map(function ($cat) {
            return $this->formatCategoryNode($cat);
        })->toArray();

        $totalCategories = CentralCategory::count();
        $activeCategories = CentralCategory::where('is_active', true)->count();
        $rootCategories = CentralCategory::whereNull('parent_id')->count();

        return [
            'tree' => $tree,
            'categories' => $categories,
            'metrics' => [
                'total_categories' => $totalCategories,
                'active_categories' => $activeCategories,
                'root_categories' => $rootCategories,
            ],
        ];
    }

    private function formatCategoryNode(CentralCategory $cat): array
    {
        return [
            'id' => $cat->id,
            'name' => $cat->name,
            'slug' => $cat->slug,
            'description' => $cat->description,
            'icon' => $cat->icon,
            'image' => $cat->image,
            'parent_id' => $cat->parent_id,
            'position' => $cat->position,
            'is_active' => $cat->is_active,
            'children' => $cat->children ? $cat->children->map(fn ($c) => $this->formatCategoryNode($c))->toArray() : [],
        ];
    }
}
