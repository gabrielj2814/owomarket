<?php

declare(strict_types=1);

namespace Src\Brand\Application\UseCase;

use Src\Brand\Infrastructure\Eloquent\Models\CentralBrand;
use Illuminate\Support\Facades\DB;
use Src\Brand\Infrastructure\Eloquent\Models\Brand;

final class SyncCentralBrandsUseCase
{
    /**
     * @return array{synced_count: int, created_count: int, updated_count: int, unchanged_count: int}
     */
    public function execute(): array
    {
        $centralBrands = CentralBrand::where('is_active', true)
            ->orderBy('position', 'asc')
            ->get();

        if ($centralBrands->isEmpty()) {
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

        DB::transaction(function () use ($centralBrands, &$created, &$updated, &$unchanged) {
            foreach ($centralBrands as $centralBrand) {
                $tenantBrand = Brand::where('central_uuid', $centralBrand->id)->first()
                    ?? Brand::where('slug', $centralBrand->slug)->first()
                    ?? Brand::whereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($centralBrand->name))])->first();

                if ($tenantBrand) {
                    $hasChanges = (
                        $tenantBrand->central_uuid !== $centralBrand->id ||
                        $tenantBrand->name !== $centralBrand->name ||
                        $tenantBrand->slug !== $centralBrand->slug ||
                        ($centralBrand->description !== null && $tenantBrand->description !== $centralBrand->description) ||
                        ($centralBrand->logo !== null && $tenantBrand->logo !== $centralBrand->logo) ||
                        (bool) $tenantBrand->is_active !== (bool) $centralBrand->is_active ||
                        (int) $tenantBrand->position !== (int) $centralBrand->position
                    );

                    if ($hasChanges) {
                        $tenantBrand->update([
                            'central_uuid' => $centralBrand->id,
                            'name' => $centralBrand->name,
                            'slug' => $centralBrand->slug,
                            'description' => $centralBrand->description ?? $tenantBrand->description,
                            'logo' => $centralBrand->logo ?? $tenantBrand->logo,
                            'is_active' => $centralBrand->is_active,
                            'position' => $centralBrand->position,
                        ]);
                        $updated++;
                    } else {
                        $unchanged++;
                    }
                } else {
                    Brand::create([
                        'central_uuid' => $centralBrand->id,
                        'name' => $centralBrand->name,
                        'slug' => $centralBrand->slug,
                        'description' => $centralBrand->description,
                        'logo' => $centralBrand->logo,
                        'is_active' => $centralBrand->is_active,
                        'position' => $centralBrand->position,
                    ]);
                    $created++;
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
