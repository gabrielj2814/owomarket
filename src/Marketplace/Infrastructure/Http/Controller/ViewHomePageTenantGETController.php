<?php

declare(strict_types=1);

namespace Src\Marketplace\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\Category\Infrastructure\Eloquent\Models\Category;
use Src\Product\Infrastructure\Eloquent\Models\Product;
use Src\Review\Infrastructure\Eloquent\Models\ProductReview;
use Src\TenantSettings\Application\UseCases\GetStoreSettingsUseCase;

final class ViewHomePageTenantGETController extends Controller
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
            if ($this->getStoreSettingsUseCase) {
                $settingsEntity = $this->getStoreSettingsUseCase->execute();
                $storeSettings = $settingsEntity->toKeyValueMap();
            }
        } catch (\Throwable) {
            $storeSettings = [
                'store_name' => 'Mi Tienda Online',
                'currency' => 'USD',
            ];
        }

        // 2. Fetch Active Categories
        $categories = [];
        try {
            $categories = Category::query()
                ->where('is_active', true)
                ->orderBy('position', 'asc')
                ->take(12)
                ->get()
                ->map(fn (Category $cat) => [
                    'id' => (string) $cat->id,
                    'name' => $cat->name,
                    'slug' => $cat->slug,
                    'image' => $cat->image,
                ])
                ->all();
        } catch (\Throwable) {
            $categories = [];
        }

        // Helper to format product data
        $formatProduct = function (Product $p): array {
            $firstImage = $p->images->first()?->url;
            $avgRating = 5.0;
            $reviewsCount = 0;

            try {
                $reviewsQuery = ProductReview::query()
                    ->where('product_id', $p->id)
                    ->where('is_approved', true);

                $reviewsCount = $reviewsQuery->count();
                if ($reviewsCount > 0) {
                    $avgRating = (float) $reviewsQuery->avg('rating');
                }
            } catch (\Throwable) {
                $avgRating = 5.0;
                $reviewsCount = 0;
            }

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
                'image' => $firstImage,
                'brand_name' => $p->brand?->name,
                'category_name' => $p->category?->name,
                'category_slug' => $p->category?->slug,
                'rating' => round($avgRating, 1),
                'reviews_count' => $reviewsCount,
            ];
        };

        // 3. Fetch Featured Products
        $featuredProducts = [];
        try {
            $featuredProducts = Product::query()
                ->with(['images', 'brand', 'category'])
                ->where('is_visible', true)
                ->where('is_featured', true)
                ->take(8)
                ->get()
                ->map($formatProduct)
                ->all();
        } catch (\Throwable) {
            $featuredProducts = [];
        }

        // 4. Fetch Newest Products
        $newProducts = [];
        try {
            $newProducts = Product::query()
                ->with(['images', 'brand', 'category'])
                ->where('is_visible', true)
                ->orderBy('created_at', 'desc')
                ->take(8)
                ->get()
                ->map($formatProduct)
                ->all();
        } catch (\Throwable) {
            $newProducts = [];
        }

        // 5. Auth User info if logged in
        $authUser = null;
        if (auth()->check()) {
            $user = auth()->user();
            $authUser = [
                'id' => (string) $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ];
        }

        return Inertia::render('marketplace/home/TenantStorefrontHomePage', [
            'domain' => $host,
            'store_settings' => $storeSettings,
            'categories' => $categories,
            'featured_products' => $featuredProducts,
            'new_products' => $newProducts,
            'auth_user' => $authUser,
        ]);
    }
}
