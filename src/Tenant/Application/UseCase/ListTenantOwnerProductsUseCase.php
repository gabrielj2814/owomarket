<?php

declare(strict_types=1);

namespace Src\Tenant\Application\UseCase;

use Src\Product\Infrastructure\Eloquent\Models\CentralProduct;
use Src\Tenant\Application\Service\TenantOwnershipVerifier;

final class ListTenantOwnerProductsUseCase
{
    public function __construct(
        private readonly TenantOwnershipVerifier $ownership
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(string $userId, ?string $filterTenantId = null, ?string $search = null): array
    {
        // Sólo las tiendas del propio usuario. Si no tiene ninguna, el catálogo va vacío:
        // NUNCA se cae hacia las tiendas de otros comerciantes.
        $tenants = $this->ownership->tenantsOf($userId);
        $tenantIds = $tenants->pluck('id')->map(fn ($id) => (string) $id)->all();

        $tenantList = $tenants->map(fn ($t) => [
            'id' => $t->id,
            'name' => $t->name ?? ucfirst((string) $t->slug),
            'slug' => $t->slug,
        ])->toArray();

        if ($tenantIds === []) {
            return [
                'tenants' => [],
                'products' => [],
                'pagination' => [
                    'total' => 0,
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 15,
                ],
                'metrics' => [
                    'total_products' => 0,
                    'published_in_central' => 0,
                    'paused_in_central' => 0,
                ],
            ];
        }

        // Un tenant_id de filtro que no pertenezca al usuario se ignora, de modo que
        // el listado nunca se amplía más allá de sus propias tiendas.
        $scopedTenantIds = ($filterTenantId !== null && in_array($filterTenantId, $tenantIds, true))
            ? [$filterTenantId]
            : $tenantIds;

        $query = CentralProduct::with(['tenant'])
            ->whereIn('tenant_id', $scopedTenantIds);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $products = $query->orderBy('created_at', 'desc')->paginate(15);

        return [
            'tenants' => $tenantList,
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
