<?php

declare(strict_types=1);

namespace Src\Marketplace\Infrastructure\Http\Controller;

use Src\Product\Infrastructure\Eloquent\Models\CentralProduct;
use Illuminate\Http\JsonResponse;
use Src\Shared\Helper\ApiResponse;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;

final class GetCentralStoresAPIController
{
    public function __invoke(): JsonResponse
    {
        $stores = Tenant::with('domains')
            ->where('status', 'active')
            ->get()
            ->map(function ($t) {
                return [
                    'id' => $t->id,
                    'name' => $t->name ?? 'Tienda Oficial',
                    'slug' => $t->slug ?? $t->id,
                    'domain' => $t->domains->isNotEmpty() ? $t->domains->first()->domain : "{$t->id}.localhost",
                    'description' => $t->description ?? 'Tienda oficial verificada en OwOMarket',
                    'logo' => $t->logo,
                    'banner' => $t->banner,
                    'products_count' => CentralProduct::where('tenant_id', $t->id)->where('is_visible', true)->count(),
                ];
            });

        return ApiResponse::success(
            data: $stores,
            message: 'Tiendas del marketplace recuperadas exitosamente'
        );
    }
}
