<?php

declare(strict_types=1);

namespace Src\Admin\Application\UseCase;

use App\Models\CentralProduct;
use Exception;

final class ModerateCentralProductUseCase
{
    /**
     * @param array{
     *     is_visible?: bool,
     *     is_featured?: bool,
     *     moderation_notes?: string|null,
     *     commission_rate?: float|null
     * } $data
     */
    public function execute(string $productId, string $adminUserId, array $data): CentralProduct
    {
        $product = CentralProduct::find($productId);

        if (! $product) {
            throw new Exception("Producto '{$productId}' no encontrado en el marketplace.", 404);
        }

        if (isset($data['is_visible'])) {
            $product->is_visible = (bool) $data['is_visible'];
        }

        if (isset($data['is_featured'])) {
            $product->is_featured = (bool) $data['is_featured'];
        }

        $meta = $product->metadata ?? [];
        if (! empty($data['moderation_notes'])) {
            $meta['moderation_history'][] = [
                'moderated_by' => $adminUserId,
                'is_visible' => $product->is_visible,
                'is_featured' => $product->is_featured,
                'notes' => $data['moderation_notes'],
                'timestamp' => now()->toIso8601String(),
            ];
        }

        if (isset($data['commission_rate'])) {
            $meta['custom_commission_rate'] = (float) $data['commission_rate'];
        }

        $product->metadata = $meta;
        $product->save();

        return $product;
    }
}
