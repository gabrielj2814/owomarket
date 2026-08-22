<?php

declare(strict_types=1);

namespace Src\Admin\Application\UseCase;

use Illuminate\Pagination\LengthAwarePaginator;
use Src\Brand\Infrastructure\Eloquent\Models\CentralBrand;

final class ListMasterBrandsUseCase
{
    /**
     * @param array{
     *     search?: string|null,
     *     is_active?: string|bool|null,
     *     per_page?: int,
     *     page?: int
     * } $filters
     * @return array{
     *     brands: LengthAwarePaginator,
     *     metrics: array{
     *         total_brands: int,
     *         active_brands: int,
     *         inactive_brands: int
     *     }
     * }
     */
    public function execute(array $filters): array
    {
        $query = CentralBrand::query();

        if (! empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '' && $filters['is_active'] !== null) {
            $isActive = filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN);
            $query->where('is_active', $isActive);
        }

        $perPage = (int) ($filters['per_page'] ?? 15);
        $brands = $query->orderBy('position', 'asc')->orderBy('name', 'asc')->paginate($perPage);

        $totalBrands = CentralBrand::count();
        $activeBrands = CentralBrand::where('is_active', true)->count();
        $inactiveBrands = CentralBrand::where('is_active', false)->count();

        return [
            'brands' => $brands,
            'metrics' => [
                'total_brands' => $totalBrands,
                'active_brands' => $activeBrands,
                'inactive_brands' => $inactiveBrands,
            ],
        ];
    }
}
