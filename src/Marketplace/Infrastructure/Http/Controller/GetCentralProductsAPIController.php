<?php

declare(strict_types=1);

namespace Src\Marketplace\Infrastructure\Http\Controller;

use App\Models\CentralProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Shared\Helper\ApiResponse;

final class GetCentralProductsAPIController
{
    public function __invoke(Request $request): JsonResponse
    {
        $query = CentralProduct::with('tenant.domains')->where('is_visible', true);

        if ($request->filled('search')) {
            $search = '%'.trim((string) $request->input('search')).'%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('description', 'like', $search)
                    ->orWhere('sku', 'like', $search)
                    ->orWhere('category_name', 'like', $search)
                    ->orWhere('brand_name', 'like', $search);
            });
        }

        if ($request->filled('category')) {
            $query->where('category_name', $request->input('category'));
        }

        if ($request->filled('brand')) {
            $query->where('brand_name', $request->input('brand'));
        }

        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->input('tenant_id'));
        }

        if ($request->filled('min_price')) {
            $query->where('price', '>=', (float) $request->input('min_price'));
        }

        if ($request->filled('max_price')) {
            $query->where('price', '<=', (float) $request->input('max_price'));
        }

        $sortBy = (string) $request->input('sort_by', 'newest');
        switch ($sortBy) {
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'name':
                $query->orderBy('name', 'asc');
                break;
            case 'newest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        $perPage = (int) $request->input('per_page', 16);
        $paginator = $query->paginate($perPage);

        $products = collect($paginator->items())->map(function (CentralProduct $p) {
            $tenant = $p->tenant;
            $domain = $tenant && $tenant->domains->isNotEmpty() ? $tenant->domains->first()->domain : null;

            return [
                'id' => $p->id,
                'tenant_id' => $p->tenant_id,
                'tenant_product_id' => $p->tenant_product_id,
                'tenant_name' => $tenant ? ($tenant->name ?? 'Tienda') : 'Tienda Asociada',
                'tenant_domain' => $domain,
                'name' => $p->name,
                'slug' => $p->slug,
                'description' => $p->description,
                'sku' => $p->sku,
                'barcode' => $p->barcode,
                'price' => (float) $p->price,
                'compare_price' => $p->compare_price !== null ? (float) $p->compare_price : null,
                'quantity' => (int) $p->quantity,
                'is_visible' => (bool) $p->is_visible,
                'is_featured' => (bool) $p->is_featured,
                'category_name' => $p->category_name,
                'brand_name' => $p->brand_name,
                'images' => $p->images ?? [],
                'variants' => $p->variants ?? [],
                'created_at' => $p->created_at?->toIso8601String(),
            ];
        });

        return ApiResponse::success(
            data: [
                'products' => $products,
                'total' => $paginator->total(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
            message: 'Productos del Marketplace Central consultados exitosamente'
        );
    }
}
