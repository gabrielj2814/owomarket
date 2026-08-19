<?php

declare(strict_types=1);

namespace Src\Marketplace\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\Category\Infrastructure\Eloquent\Models\Category;
use Src\Product\Infrastructure\Eloquent\Models\Product;
use Src\Product\Infrastructure\Eloquent\Models\ProductVariant;
use Src\Review\Infrastructure\Eloquent\Models\ProductReview;
use Src\TenantSettings\Application\UseCases\GetStoreSettingsUseCase;

final class ViewProductDetailTenantGETController extends Controller
{
    public function __construct(
        private readonly ?GetStoreSettingsUseCase $getStoreSettingsUseCase = null
    ) {}

    public function index(Request $request, string $slug): Response
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

        // 2. Fetch Product with Relationships
        $product = Product::query()
            ->with(['images', 'brand', 'category', 'variants.attributeValues.attribute'])
            ->where('slug', $slug)
            ->where('is_visible', true)
            ->first();

        if (! $product) {
            abort(404, 'Producto no encontrado');
        }

        // Format Images
        $images = $product->images->pluck('url')->filter()->values()->all();
        if (empty($images)) {
            $images = [];
        }

        // Format Variants
        $variants = $product->variants->map(function (ProductVariant $v) {
            $attrs = [];
            if (is_array($v->attributes)) {
                $attrs = $v->attributes;
            } else {
                foreach ($v->attributeValues as $av) {
                    $attrName = $av->attribute?->name ?? 'Opción';
                    $attrs[$attrName] = $av->value;
                }
            }

            return [
                'id' => (string) $v->id,
                'sku' => $v->sku,
                'price' => (float) $v->price,
                'compare_price' => $v->compare_price ? (float) $v->compare_price : null,
                'quantity' => (int) $v->quantity,
                'attributes' => $attrs,
                'image' => $v->image,
            ];
        })->values()->all();

        // 3. Fetch Reviews and Calculate Summary
        $reviewsCollection = ProductReview::query()
            ->with('customer')
            ->where('product_id', $product->id)
            ->where('is_approved', true)
            ->orderBy('is_verified', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $totalReviews = $reviewsCollection->count();
        $avgRating = $totalReviews > 0 ? (float) $reviewsCollection->avg('rating') : 5.0;

        $breakdown = [
            5 => $reviewsCollection->where('rating', 5)->count(),
            4 => $reviewsCollection->where('rating', 4)->count(),
            3 => $reviewsCollection->where('rating', 3)->count(),
            2 => $reviewsCollection->where('rating', 2)->count(),
            1 => $reviewsCollection->where('rating', 1)->count(),
        ];

        $reviewsList = $reviewsCollection->map(function (ProductReview $r) {
            $authorName = $r->customer?->name;
            if (! $authorName) {
                $authorName = $r->is_verified ? 'Comprador Verificado' : 'Cliente';
            }

            return [
                'id' => (string) $r->id,
                'rating' => (int) $r->rating,
                'title' => $r->title,
                'comment' => (string) $r->comment,
                'author_name' => $authorName,
                'response' => $r->response,
                'responded_at' => $r->responded_at?->format('d/m/Y'),
                'is_verified' => (bool) $r->is_verified,
                'created_at' => $r->created_at?->format('d/m/Y') ?? '',
            ];
        })->values()->all();

        $productDetail = [
            'id' => (string) $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'sku' => $product->sku,
            'description' => $product->description,
            'price' => (float) $product->price,
            'compare_price' => $product->compare_price ? (float) $product->compare_price : null,
            'quantity' => (int) $product->quantity,
            'is_featured' => (bool) $product->is_featured,
            'is_visible' => (bool) $product->is_visible,
            'images' => $images,
            'brand_name' => $product->brand?->name,
            'category_name' => $product->category?->name,
            'category_slug' => $product->category?->slug,
            'specifications' => is_array($product->specifications) ? $product->specifications : [],
            'variants' => $variants,
            'rating' => round($avgRating, 1),
            'reviews_count' => $totalReviews,
        ];

        // 4. Fetch Active Categories for Navbar
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

        // 5. Fetch Related Products (same category)
        $relatedProducts = [];
        if ($product->category_id) {
            $relatedProducts = Product::query()
                ->with(['images', 'brand', 'category'])
                ->where('category_id', $product->category_id)
                ->where('id', '!=', $product->id)
                ->where('is_visible', true)
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
        }

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

        return Inertia::render('marketplace/product/TenantProductDetailPage', [
            'domain' => $host,
            'store_settings' => $storeSettings,
            'categories' => $categories,
            'product' => $productDetail,
            'reviews' => $reviewsList,
            'reviews_summary' => [
                'avg_rating' => round($avgRating, 1),
                'total_reviews' => $totalReviews,
                'rating_breakdown' => $breakdown,
            ],
            'related_products' => $relatedProducts,
            'auth_user' => $authUser,
        ]);
    }
}
