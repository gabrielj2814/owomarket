<?php

declare(strict_types=1);

namespace Src\Tenant\Application\UseCase;

use Exception;
use Src\Product\Infrastructure\Eloquent\Models\CentralProduct;
use Src\Tenant\Application\Service\TenantOwnershipVerifier;

final class ToggleProductMarketplacePublicationUseCase
{
    public function __construct(
        private readonly TenantOwnershipVerifier $ownership
    ) {}

    /**
     * @return array{product_id: string, is_visible: bool, message: string}
     */
    public function execute(string $userId, string $productId, ?bool $status = null): array
    {
        $product = CentralProduct::find($productId);

        if (! $product) {
            throw new Exception('Producto no encontrado en el catálogo central.', 404);
        }

        // El producto sólo puede publicarse o despublicarse por el propietario de la
        // tienda a la que pertenece. Antes este método recibía $userId y lo ignoraba,
        // por lo que cualquiera podía despublicar el catálogo de cualquier comercio.
        $this->ownership->ensureOwns($userId, (string) $product->tenant_id);

        $newStatus = $status !== null ? $status : ! $product->is_visible;
        $product->is_visible = $newStatus;
        $product->save();

        $statusText = $newStatus
            ? 'publicado en el Marketplace Central'
            : 'despublicado del Marketplace Central (sigue activo en tu tienda privada)';

        return [
            'product_id' => $product->id,
            'is_visible' => $newStatus,
            'message' => "El producto '{$product->name}' ha sido {$statusText}.",
        ];
    }
}
