<?php

declare(strict_types=1);

namespace Src\Tenant\Application\UseCase;

use App\Models\CentralProduct;
use Exception;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;

final class ToggleProductMarketplacePublicationUseCase
{
    /**
     * @return array{product_id: string, is_visible: bool, message: string}
     */
    public function execute(string $userId, string $productId, ?bool $status = null): array
    {
        $product = CentralProduct::find($productId);
        if (! $product) {
            throw new Exception('Producto no encontrado en el catálogo central.', 404);
        }

        $newStatus = $status !== null ? $status : ! $product->is_visible;
        $product->is_visible = $newStatus;
        $product->save();

        $statusText = $newStatus ? 'publicado en el Marketplace Central' : 'despublicado del Marketplace Central (sigue activo en tu tienda privada)';

        return [
            'product_id' => $product->id,
            'is_visible' => $newStatus,
            'message' => "El producto '{$product->name}' ha sido {$statusText}.",
        ];
    }
}
