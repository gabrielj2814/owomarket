<?php

declare(strict_types=1);

namespace Src\Category\Application\UseCase;

use App\Models\CentralCategory;
use Illuminate\Support\Facades\DB;
use Src\Category\Infrastructure\Eloquent\Models\Category;

final class SyncCentralCategoriesUseCase
{
    /**
     * @return array{synced_count: int, created_count: int, updated_count: int}
     */
    public function execute(): array
    {
        $centralCategories = CentralCategory::where('is_active', true)
            ->orderBy('position', 'asc')
            ->get();

        if ($centralCategories->isEmpty()) {
            return [
                'synced_count' => 0,
                'created_count' => 0,
                'updated_count' => 0,
            ];
        }

        $created = 0;
        $updated = 0;

        DB::transaction(function () use ($centralCategories, &$created, &$updated) {
            // First pass: upsert all categories by central_uuid or slug
            foreach ($centralCategories as $centralCat) {
                $tenantCat = Category::where('central_uuid', $centralCat->id)->first()
                    ?? Category::where('slug', $centralCat->slug)->first();

                if ($tenantCat) {
                    $tenantCat->update([
                        'central_uuid' => $centralCat->id,
                        'name' => $centralCat->name,
                        'slug' => $centralCat->slug,
                        'description' => $centralCat->description ?? $tenantCat->description,
                        'image' => $centralCat->image ?? $tenantCat->image,
                        'is_active' => $centralCat->is_active,
                        'position' => $centralCat->position,
                    ]);
                    $updated++;
                } else {
                    Category::create([
                        'central_uuid' => $centralCat->id,
                        'name' => $centralCat->name,
                        'slug' => $centralCat->slug,
                        'description' => $centralCat->description,
                        'image' => $centralCat->image,
                        'is_active' => $centralCat->is_active,
                        'position' => $centralCat->position,
                        'parent_id' => null,
                    ]);
                    $created++;
                }
            }

            // Second pass: resolve parent_id hierarchy using central_uuid
            foreach ($centralCategories as $centralCat) {
                if (!empty($centralCat->parent_id)) {
                    $parentTenantCat = Category::where('central_uuid', $centralCat->parent_id)->first();
                    $currentTenantCat = Category::where('central_uuid', $centralCat->id)->first();

                    if ($parentTenantCat && $currentTenantCat && $currentTenantCat->parent_id !== $parentTenantCat->id) {
                        $currentTenantCat->update(['parent_id' => $parentTenantCat->id]);
                    }
                }
            }
        });

        return [
            'synced_count' => $created + $updated,
            'created_count' => $created,
            'updated_count' => $updated,
        ];
    }
}
