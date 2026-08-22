<?php

declare(strict_types=1);

namespace Src\Marketplace\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Marketplace\Application\Service\StorefrontCartRevalidator;
use Src\Shared\Helper\ApiResponse;

/**
 * Hallazgo G4: el carrito vivia en `localStorage` con el precio y el stock congelados el
 * dia en que se anadio cada producto, y nadie los revalidaba nunca.
 *
 * Es publico a proposito: el carrito del storefront funciona sin sesion, igual que el
 * resto de rutas de `routes/tenant.php`. No expone nada que no devuelva ya la ficha de
 * producto.
 */
final class RevalidateStorefrontCartPOSTController extends Controller
{
    public function __construct(
        private readonly StorefrontCartRevalidator $revalidator
    ) {}

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'items' => ['required', 'array', 'max:100'],
            'items.*.product_id' => ['required', 'string'],
            'items.*.variant_id' => ['nullable', 'string'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $result = $this->revalidator->revalidate($request->input('items', []));

        return ApiResponse::success(
            data: $result,
            message: $result['has_changes']
                ? 'El carrito ha cambiado desde la ultima vez.'
                : 'El carrito esta al dia.'
        );
    }
}
