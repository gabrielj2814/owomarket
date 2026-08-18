<?php

declare(strict_types=1);

namespace Src\Marketplace\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\Category\Infrastructure\Eloquent\Models\Category;
use Src\Product\Infrastructure\Eloquent\Models\Product;
use Src\TenantSettings\Application\UseCases\GetStoreSettingsUseCase;

final class ViewCartTenantGETController extends Controller
{
    public function __construct(
        private readonly ?GetStoreSettingsUseCase $getStoreSettingsUseCase = null
    ) {}

    public function index(Request $request): Response
    {
        $host = $request->getHost();

        // 1. Fetch Store Settings
        $storeSettings = [];
        try {
            $useCase = $this->getStoreSettingsUseCase ?? app(GetStoreSettingsUseCase::class);
            $settingsEntity = $useCase->execute();
            $storeSettings = $settingsEntity->toKeyValueMap();
        } catch (\Throwable) {
            $storeSettings = [
                'store_name' => 'Mi Tienda Online',
                'currency' => 'USD',
            ];
        }

        if (empty($storeSettings)) {
            $storeSettings = [
                'store_name' => 'Mi Tienda Online',
                'currency' => 'USD',
            ];
        }

        // 2. Fetch Active Categories for Navbar
        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('position', 'asc')
            ->get()
            ->map(fn (Category $cat) => [
                'id' => (string) $cat->id,
                'name' => $cat->name,
                'slug' => $cat->slug,
                'image' => $cat->image,
            ])
            ->all();

        // 3. Recommended/Featured Products
        $recommendedProducts = Product::query()
            ->with(['images', 'brand', 'category'])
            ->where('is_visible', true)
            ->where('is_featured', true)
            ->take(4)
            ->get()
            ->map(function (Product $p) {
                return [
                    'id' => (string) $p->id,
                    'name' => $p->name,
                    'slug' => $p->slug,
                    'sku' => $p->sku,
                    'price' => (float) $p->price,
                    'compare_price' => $p->compare_price ? (float) $p->compare_price : null,
                    'quantity' => (int) $p->quantity,
                    'is_featured' => (bool) $p->is_featured,
                    'is_visible' => (bool) $p->is_visible,
                    'image' => $p->images->first()?->url,
                    'brand_name' => $p->brand?->name,
                    'category_name' => $p->category?->name,
                    'category_slug' => $p->category?->slug,
                    'rating' => 5.0,
                    'reviews_count' => 0,
                ];
            })
            ->all();

        // 4. Auth User info if logged in
        $authUser = null;
        if (auth()->check()) {
            $user = auth()->user();
            $authUser = [
                'id' => (string) $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ];
        }

        return Inertia::render('marketplace/cart/TenantCartPage', [
            'domain' => $host,
            'store_settings' => $storeSettings,
            'categories' => $categories,
            'recommended_products' => $recommendedProducts,
            'auth_user' => $authUser,
        ]);
    }
}
