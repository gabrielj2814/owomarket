<?php

declare(strict_types=1);

namespace Src\Category\Application\UseCase;

use Src\Category\Infrastructure\Eloquent\Models\CentralCategory;
use Illuminate\Support\Facades\DB;
use Src\Category\Infrastructure\Eloquent\Models\Category;

final class SyncCentralCategoriesUseCase
{
    /**
     * @return array{synced_count: int, created_count: int, updated_count: int, unchanged_count: int}
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
                'unchanged_count' => 0,
            ];
        }

        $created = 0;
        $updated = 0;
        $unchanged = 0;

        DB::transaction(function () use ($centralCategories, &$created, &$updated, &$unchanged) {
            // First pass: upsert all categories with 3-tier matching (central_uuid -> slug -> LOWER(TRIM(name)))
            foreach ($centralCategories as $centralCat) {
                $tenantCat = Category::where('central_uuid', $centralCat->id)->first()
                    ?? Category::where('slug', $centralCat->slug)->first()
                    ?? Category::whereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($centralCat->name))])->first();

                if ($tenantCat) {
                    $hasChanges = (
                        $tenantCat->central_uuid !== $centralCat->id ||
                        $tenantCat->name !== $centralCat->name ||
                        $tenantCat->slug !== $centralCat->slug ||
                        ($centralCat->description !== null && $tenantCat->description !== $centralCat->description) ||
                        ($centralCat->image !== null && $tenantCat->image !== $centralCat->image) ||
                        (bool) $tenantCat->is_active !== (bool) $centralCat->is_active ||
                        (int) $tenantCat->position !== (int) $centralCat->position
                    );

                    if ($hasChanges) {
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
                        $unchanged++;
                    }
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
            'synced_count' => $created + $updated + $unchanged,
            'created_count' => $created,
            'updated_count' => $updated,
            'unchanged_count' => $unchanged,
        ];
    }
}
