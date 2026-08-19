<?php

declare(strict_types=1);

namespace Src\Marketplace\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use App\Models\CentralProduct;
use Inertia\Response;

final class ViewProductDetailCentralGETController extends Controller
{
    public function index(string $slug): Response
    {
        $product = CentralProduct::with('tenant.domains')
            ->where('is_visible', true)
            ->where(function ($q) use ($slug) {
                $q->where('slug', $slug)->orWhere('id', $slug)->orWhere('tenant_product_id', $slug);
            })
            ->first();

        if (! $product) {
            abort(404, 'Producto no disponible en el marketplace central.');
        }

        $tenant = $product->tenant;
        $domain = $tenant && $tenant->domains->isNotEmpty() ? $tenant->domains->first()->domain : null;

        $store = [
            'id' => $tenant?->id ?? $product->tenant_id,
            'name' => $tenant?->name ?? 'Tienda Asociada',
            'slug' => $tenant?->slug ?? $tenant?->id,
            'domain' => $domain,
            'description' => $tenant?->description,
            'logo' => $tenant?->logo,
            'banner' => $tenant?->banner,
        ];

        return inertia()->render('marketplace/product/CentralProductDetailPage', [
            'domain' => request()->getHost(),
            'slug' => $slug,
            'product_initial' => [
                'id' => $product->id,
                'tenant_id' => $product->tenant_id,
                'tenant_product_id' => $product->tenant_product_id,
                'tenant_name' => $tenant?->name ?? 'Tienda Asociada',
                'tenant_domain' => $domain,
                'name' => $product->name,
                'slug' => $product->slug,
                'description' => $product->description,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'price' => (float) $product->price,
                'compare_price' => $product->compare_price !== null ? (float) $product->compare_price : null,
                'quantity' => (int) $product->quantity,
                'is_visible' => (bool) $product->is_visible,
                'is_featured' => (bool) $product->is_featured,
                'category_name' => $product->category_name,
                'brand_name' => $product->brand_name,
                'images' => $product->images ?? [],
                'variants' => $product->variants ?? [],
                'specifications' => $product->specifications,
                'metadata' => $product->metadata,
            ],
            'store' => $store,
        ]);
    }
}
