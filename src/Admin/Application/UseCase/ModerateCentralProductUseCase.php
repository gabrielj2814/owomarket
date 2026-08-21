<?php

declare(strict_types=1);

namespace Src\Admin\Application\UseCase;

use Exception;
use Src\Product\Infrastructure\Eloquent\Models\CentralProduct;

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

            // Hallazgos E1/E2: desde que la sincronización es automática, `is_visible` se
            // recalcula en cada guardado del producto en la tienda. Sin esta bandera, al
            // comerciante le bastaría con editar el producto para volver a publicar algo
            // que el moderador acababa de retirar.
            $product->is_blocked_by_admin = ! (bool) $data['is_visible'];
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
