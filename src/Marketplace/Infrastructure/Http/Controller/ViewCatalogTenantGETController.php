<?php

declare(strict_types=1);

namespace Src\Marketplace\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\Brand\Infrastructure\Eloquent\Models\Brand;
use Src\Category\Infrastructure\Eloquent\Models\Category;
use Src\Product\Infrastructure\Eloquent\Models\Product;
use Src\Review\Infrastructure\Eloquent\Models\ProductReview;
use Src\TenantSettings\Application\UseCases\GetStoreSettingsUseCase;

final class ViewCatalogTenantGETController extends Controller
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

        // 2. Fetch Active Categories with product count
        $categories = [];
        try {
            $categories = Category::query()
                ->where('is_active', true)
                ->orderBy('position', 'asc')
                ->get()
                ->map(fn (Category $cat) => [
                    'id' => (string) $cat->id,
                    'name' => $cat->name,
                    'slug' => $cat->slug,
                    'image' => $cat->image,
                    'products_count' => Product::where('category_id', $cat->id)->where('is_visible', true)->count(),
                ])
                ->all();
        } catch (\Throwable) {
            $categories = [];
        }

        // 3. Fetch Active Brands with product count
        $brands = [];
        try {
            $brands = Brand::query()
                ->where('is_active', true)
                ->orderBy('name', 'asc')
                ->get()
                ->map(fn (Brand $brand) => [
                    'id' => (string) $brand->id,
                    'name' => $brand->name,
                    'slug' => $brand->slug,
                    'products_count' => Product::where('brand_id', $brand->id)->where('is_visible', true)->count(),
                ])
                ->all();
        } catch (\Throwable) {
            $brands = [];
        }

        // 4. Catalog Price Boundaries
        $minPrice = 0.0;
        $maxPrice = 500000.0;
        try {
            $minPrice = (float) (Product::where('is_visible', true)->min('price') ?? 0);
            $maxPrice = (float) (Product::where('is_visible', true)->max('price') ?? 500000);
        } catch (\Throwable) {
        }

        // 5. Build Filtered Products Query
        $query = Product::query()
            ->with(['images', 'brand', 'category'])
            ->where('is_visible', true);

        // Filter: Search Term
        $search = trim((string) ($request->input('search') ?? $request->input('q') ?? ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhereHas('brand', fn ($b) => $b->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('category', fn ($c) => $c->where('name', 'like', "%{$search}%"));
            });
        }

        // Filter: Category Slug
        $categorySlug = (string) $request->input('category', '');
        if ($categorySlug !== '') {
            $query->whereHas('category', fn ($c) => $c->where('slug', $categorySlug));
        }

        // Filter: Brand Slug
        $brandSlug = (string) $request->input('brand', '');
        if ($brandSlug !== '') {
            $query->whereHas('brand', fn ($b) => $b->where('slug', $brandSlug));
        }

        // Filter: Price Range
        if ($request->filled('min_price')) {
            $query->where('price', '>=', (float) $request->input('min_price'));
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', (float) $request->input('max_price'));
        }

        // Filter: Flags (on_sale, in_stock)
        $filterFlag = (string) $request->input('filter', '');
        if ($filterFlag === 'on_sale') {
            $query->whereNotNull('compare_price')->whereRaw('compare_price > price');
        } elseif ($filterFlag === 'in_stock') {
            $query->where('quantity', '>', 0);
        }

        // Sorting
        $sort = (string) $request->input('sort', 'latest');
        match ($sort) {
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'name_asc' => $query->orderBy('name', 'asc'),
            'name_desc' => $query->orderBy('name', 'desc'),
            default => $query->orderBy('created_at', 'desc'),
        };

        // Paginate results (12 per page)
        $perPage = 12;
        $paginated = $query->paginate($perPage)->withQueryString();

        // Format products with ratings
        $formattedProducts = collect($paginated->items())->map(function (Product $p) {
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
        });

        // 6. Auth User info if logged in
        $authUser = null;
        if (auth()->check()) {
            $user = auth()->user();
            $authUser = [
                'id' => (string) $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ];
        }

        return Inertia::render('marketplace/catalog/TenantCatalogPage', [
            'domain' => $host,
            'store_settings' => $storeSettings,
            'categories' => $categories,
            'brands' => $brands,
            'price_bounds' => [
                'min' => $minPrice,
                'max' => $maxPrice,
            ],
            'products' => [
                'data' => $formattedProducts,
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'total' => $paginated->total(),
                'per_page' => $paginated->perPage(),
                'links' => $paginated->linkCollection()->toArray(),
            ],
            'filters' => [
                'search' => $search,
                'category' => $categorySlug,
                'brand' => $brandSlug,
                'min_price' => $request->input('min_price'),
                'max_price' => $request->input('max_price'),
                'sort' => $sort,
                'filter' => $filterFlag,
            ],
            'auth_user' => $authUser,
        ]);
    }
}
