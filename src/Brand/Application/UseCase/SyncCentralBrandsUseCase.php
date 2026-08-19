<?php

declare(strict_types=1);

namespace Src\Brand\Application\UseCase;

use App\Models\CentralBrand;
use Illuminate\Support\Facades\DB;
use Src\Brand\Infrastructure\Eloquent\Models\Brand;

final class SyncCentralBrandsUseCase
{
    /**
     * @return array{synced_count: int, created_count: int, updated_count: int}
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
            ];
        }

        $created = 0;
        $updated = 0;

        DB::transaction(function () use ($centralBrands, &$created, &$updated) {
            foreach ($centralBrands as $centralBrand) {
                $tenantBrand = Brand::where('central_uuid', $centralBrand->id)->first()
                    ?? Brand::where('slug', $centralBrand->slug)->first();

                if ($tenantBrand) {
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
            'synced_count' => $created + $updated,
            'created_count' => $created,
            'updated_count' => $updated,
        ];
    }
}
