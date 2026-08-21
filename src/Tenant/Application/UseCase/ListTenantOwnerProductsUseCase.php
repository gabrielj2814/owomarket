<?php

declare(strict_types=1);

namespace Src\Tenant\Application\UseCase;

use Src\Product\Infrastructure\Eloquent\Models\CentralProduct;
use Illuminate\Support\Facades\Schema;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;

final class ListTenantOwnerProductsUseCase
{
    /**
     * @return array<string, mixed>
     */
    public function execute(string $userId, ?string $filterTenantId = null, ?string $search = null): array
    {
        $tenants = Tenant::whereHas('users', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })->get();

        if ($tenants->isEmpty()) {
            $tenants = Tenant::where('status', 'active')->limit(10)->get();
        }

        $tenantIds = $tenants->pluck('id')->toArray();

        $query = CentralProduct::with(['tenant'])
            ->whereIn('tenant_id', $tenantIds);

        if ($filterTenantId && in_array($filterTenantId, $tenantIds, true)) {
            $query->where('tenant_id', $filterTenantId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $products = $query->orderBy('created_at', 'desc')->paginate(15);

        return [
            'tenants' => $tenants->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name ?? ucfirst($t->slug),
                'slug' => $t->slug,
            ])->toArray(),
            'products' => $products->items(),
            'pagination' => [
                'total' => $products->total(),
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
            ],
            'metrics' => [
                'total_products' => CentralProduct::whereIn('tenant_id', $tenantIds)->count(),
                'published_in_central' => CentralProduct::whereIn('tenant_id', $tenantIds)->where('is_visible', true)->count(),
                'paused_in_central' => CentralProduct::whereIn('tenant_id', $tenantIds)->where('is_visible', false)->count(),
            ],
        ];
    }
}
