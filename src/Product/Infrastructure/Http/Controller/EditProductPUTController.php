<?php

declare(strict_types=1);

namespace Src\Product\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Src\Product\Application\DTOs\ProductImageData;
use Src\Product\Application\DTOs\ProductVariantData;
use Src\Product\Application\UseCase\EditProductUseCase;
use Src\Product\Infrastructure\Http\Request\EditProductFormRequest;
use Src\Shared\Helper\ApiResponse;

final class EditProductPUTController
{
    public function __construct(
        private readonly EditProductUseCase $useCase
    ) {}

    public function __invoke(string $id, EditProductFormRequest $request): JsonResponse
    {
        try {
            $images = [];
            if ($request->has('images') && is_array($request->input('images'))) {
                foreach ($request->input('images') as $img) {
                    $images[] = new ProductImageData(
                        imagePath: (string) ($img['image_path'] ?? ''),
                        altText: isset($img['alt_text']) ? (string) $img['alt_text'] : null,
                        isDefault: (bool) ($img['is_default'] ?? false),
                        order: (int) ($img['order'] ?? 0),
                        id: isset($img['id']) ? (string) $img['id'] : null
                    );
                }
            }

            $variants = [];
            if ($request->has('variants') && is_array($request->input('variants'))) {
                foreach ($request->input('variants') as $v) {
                    $variants[] = new ProductVariantData(
                        sku: (string) ($v['sku'] ?? ''),
                        price: (float) ($v['price'] ?? 0),
                        comparePrice: isset($v['compare_price']) ? (float) $v['compare_price'] : null,
                        costPrice: isset($v['cost_price']) ? (float) $v['cost_price'] : null,
                        quantity: (int) ($v['quantity'] ?? 0),
                        image: isset($v['image']) ? (string) $v['image'] : null,
                        weight: isset($v['weight']) ? (float) $v['weight'] : null,
                        attributes: isset($v['attributes']) && is_array($v['attributes']) ? $v['attributes'] : [],
                        attributeValueIds: isset($v['attribute_value_ids']) && is_array($v['attribute_value_ids']) ? $v['attribute_value_ids'] : [],
                        id: isset($v['id']) ? (string) $v['id'] : null
                    );
                }
            }

            $product = $this->useCase->execute(
                id: $id,
                name: (string) $request->input('name'),
                slug: (string) $request->input('slug'),
                sku: (string) $request->input('sku'),
                price: (float) $request->input('price'),
                quantity: (int) $request->input('quantity', 0),
                comparePrice: $request->filled('compare_price') ? (float) $request->input('compare_price') : null,
                costPrice: $request->filled('cost_price') ? (float) $request->input('cost_price') : null,
                minQuantity: (int) $request->input('min_quantity', 1),
                maxQuantity: (int) $request->input('max_quantity', 100),
                trackQuantity: (bool) $request->input('track_quantity', true),
                isVisible: (bool) $request->input('is_visible', true),
                isFeatured: (bool) $request->input('is_featured', false),
                isDigital: (bool) $request->input('is_digital', false),
                description: $request->filled('description') ? (string) $request->input('description') : null,
                shortDescription: $request->filled('short_description') ? (string) $request->input('short_description') : null,
                barcode: $request->filled('barcode') ? (string) $request->input('barcode') : null,
                digitalProductUrl: $request->filled('digital_product_url') ? (string) $request->input('digital_product_url') : null,
                weight: $request->filled('weight') ? (float) $request->input('weight') : null,
                height: $request->filled('height') ? (float) $request->input('height') : null,
                width: $request->filled('width') ? (float) $request->input('width') : null,
                length: $request->filled('length') ? (float) $request->input('length') : null,
                categoryId: $request->filled('category_id') ? (int) $request->input('category_id') : null,
                brandId: $request->filled('brand_id') ? (int) $request->input('brand_id') : null,
                publishedAt: $request->filled('published_at') ? (string) $request->input('published_at') : null,
                seo: $request->input('seo'),
                specifications: $request->input('specifications'),
                metadata: $request->input('metadata'),
                images: $images,
                variants: $variants
            );

            return ApiResponse::success(
                data: $product->toArray(),
                message: 'Producto actualizado exitosamente',
                code: 200
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                code: (int) ($e->getCode() ?: 400)
            );
        }
    }
}
