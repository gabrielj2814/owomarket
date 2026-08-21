<?php

declare(strict_types=1);

namespace Src\Marketplace\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Src\Product\Infrastructure\Eloquent\Models\CentralProduct;
use Inertia\Response;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;

final class ViewHomePageCentralGETController extends Controller
{
    public function index(): Response
    {
        $host = request()->getHost();

        // 1. Featured Stores
        $stores = Tenant::with('domains')
            ->where('status', 'active')
            ->take(8)
            ->get()
            ->map(function ($t) {
                return [
                    'id' => $t->id,
                    'name' => $t->name ?? 'Tienda Oficial',
                    'slug' => $t->slug ?? $t->id,
                    'domain' => $t->domains->isNotEmpty() ? $t->domains->first()->domain : "{$t->id}.localhost",
                    'description' => $t->description ?? 'Tienda asociada en OwOMarket',
                    'logo' => $t->logo,
                    'banner' => $t->banner,
                    'products_count' => CentralProduct::where('tenant_id', $t->id)->where('is_visible', true)->count(),
                ];
            });

        // 2. Featured Products
        $featuredProducts = CentralProduct::with('tenant.domains')
            ->where('is_visible', true)
            ->where('is_featured', true)
            ->take(8)
            ->get()
            ->map(fn ($p) => $this->mapProduct($p));

        if ($featuredProducts->isEmpty()) {
            $featuredProducts = CentralProduct::with('tenant.domains')
                ->where('is_visible', true)
                ->take(8)
                ->get()
                ->map(fn ($p) => $this->mapProduct($p));
        }

        // 3. Recent Products
        $recentProducts = CentralProduct::with('tenant.domains')
            ->where('is_visible', true)
            ->orderBy('created_at', 'desc')
            ->take(12)
            ->get()
            ->map(fn ($p) => $this->mapProduct($p));

        // 4. Categories breakdown
        $categories = CentralProduct::where('is_visible', true)
            ->whereNotNull('category_name')
            ->where('category_name', '!=', '')
            ->selectRaw('category_name as name, count(*) as count')
            ->groupBy('category_name')
            ->orderBy('count', 'desc')
            ->take(10)
            ->get()
            ->toArray();

        return inertia()->render('marketplace/home/centralHomePage', [
            'domain' => $host,
            'initial_data' => [
                'featured_stores' => $stores,
                'featured_products' => $featuredProducts,
                'recent_products' => $recentProducts,
                'categories' => $categories,
            ],
        ]);
    }

    private function mapProduct(CentralProduct $p): array
    {
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
    }
}
