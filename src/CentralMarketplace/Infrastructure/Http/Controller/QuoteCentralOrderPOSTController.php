<?php

declare(strict_types=1);

namespace Src\CentralMarketplace\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\CentralMarketplace\Application\Service\CentralItemPriceResolver;
use Src\CentralMarketplace\Application\Service\CentralOrderChargesCalculator;
use Src\Shared\Helper\ApiResponse;
use Throwable;

/**
 * Presupuesto de un pedido central: envio, impuestos y cupones, tienda por tienda
 * (hallazgos N34 y N28).
 *
 * El checkout central mostraba el **subtotal puro como total**, asi que el importe que el
 * comprador transferia no coincidia con el que se registraba. Este endpoint devuelve lo
 * mismo que calculara el servidor al crear el pedido, para que la pantalla no invente.
 */
final class QuoteCentralOrderPOSTController extends Controller
{
    public function __construct(
        private readonly CentralItemPriceResolver $priceResolver,
        private readonly CentralOrderChargesCalculator $chargesCalculator
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.tenant_id' => ['required', 'string'],
            'items.*.product_id' => ['required', 'string'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'shipping_address' => ['nullable', 'array'],
            'coupons' => ['nullable', 'array'],
        ]);

        $resolved = [];

        foreach ($request->input('items', []) as $item) {
            try {
                $resolved[] = $this->priceResolver->resolve($item);
            } catch (Throwable) {
                // Una linea que ya no se puede servir no debe tumbar el presupuesto: de eso
                // se encarga `/cart/revalidate`, que dice exactamente cual es.
                continue;
            }
        }

        if ($resolved === []) {
            return ApiResponse::error(message: 'Ninguna linea del carrito sigue disponible.', code: 422);
        }

        $charges = $this->chargesCalculator->calculate(
            $resolved,
            (array) $request->input('shipping_address', []),
            (array) $request->input('coupons', [])
        );

        return ApiResponse::success(
            data: $charges + ['total' => round(
                $charges['subtotal'] + $charges['shipping'] + $charges['tax'] - $charges['discount'],
                2
            )],
            message: 'Presupuesto calculado.'
        );
    }
}
