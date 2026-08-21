<?php

declare(strict_types=1);

namespace Src\Admin\Application\UseCase;

use Src\Product\Infrastructure\Eloquent\Models\CentralProduct;
use Illuminate\Pagination\LengthAwarePaginator;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;

final class ListProductsForModerationUseCase
{
    /**
     * @param array{
     *     tenant_id?: string|null,
     *     is_visible?: string|bool|null,
     *     is_featured?: string|bool|null,
     *     search?: string|null,
     *     per_page?: int,
     *     page?: int
     * } $filters
     * @return array{
     *     products: LengthAwarePaginator,
     *     metrics: array{
     *         total_products: int,
     *         approved_products: int,
     *         pending_products: int,
     *         featured_products: int
     *     },
     *     tenants: array<array{id: string, name: string}>
     * }
     */
    public function execute(array $filters): array
    {
        $query = CentralProduct::query()->with('tenant');

        if (! empty($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }

        if (isset($filters['is_visible']) && $filters['is_visible'] !== '' && $filters['is_visible'] !== null) {
            $isVisible = filter_var($filters['is_visible'], FILTER_VALIDATE_BOOLEAN);
            $query->where('is_visible', $isVisible);
        }

        if (isset($filters['is_featured']) && $filters['is_featured'] !== '' && $filters['is_featured'] !== null) {
            $isFeatured = filter_var($filters['is_featured'], FILTER_VALIDATE_BOOLEAN);
            $query->where('is_featured', $isFeatured);
        }

        if (! empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('brand_name', 'like', "%{$search}%")
                    ->orWhere('category_name', 'like', "%{$search}%");
            });
        }

        $perPage = (int) ($filters['per_page'] ?? 15);
        $products = $query->orderBy('created_at', 'desc')->paginate($perPage);

        $totalProducts = CentralProduct::count();
        $approvedProducts = CentralProduct::where('is_visible', true)->count();
        $pendingProducts = CentralProduct::where('is_visible', false)->count();
        $featuredProducts = CentralProduct::where('is_featured', true)->count();

        $tenantsList = Tenant::select('id', 'name')->orderBy('name', 'asc')->get()->toArray();

        return [
            'products' => $products,
            'metrics' => [
                'total_products' => $totalProducts,
                'approved_products' => $approvedProducts,
                'pending_products' => $pendingProducts,
                'featured_products' => $featuredProducts,
            ],
            'tenants' => $tenantsList,
        ];
    }
}
