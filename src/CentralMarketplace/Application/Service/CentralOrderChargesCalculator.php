<?php

declare(strict_types=1);

namespace Src\CentralMarketplace\Application\Service;

use Src\Coupon\Application\UseCase\ValidateCouponUseCase;
use Src\Shipping\Application\UseCase\CalculateShippingOptionsUseCase;
use Src\Tax\Application\UseCase\CalculateTaxUseCase;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;
use Throwable;

/**
 * Calcula envío, impuestos y descuento de un pedido central, **tienda por tienda**.
 *
 * Hallazgos N34 y N28: el checkout central no incluía ni envío ni impuestos —el total
 * mostrado era el subtotal puro, así que el importe que el comprador transfería no
 * coincidía con nada— y aceptaba un `coupon_code` que **nadie validaba ni consumía**.
 *
 * Además, `shipping_amount` y `discount_amount` se tomaban tal cual del navegador, que es
 * exactamente el error de B1 con otro nombre.
 *
 * **Decisión de negocio (22/08/2026):** cada tienda calcula lo suyo con sus propias
 * tarifas, las que ya tiene configuradas en su base, y el pedido central suma. Y los
 * cupones son de tienda: un código sólo descuenta las líneas de la tienda que lo emitió, y
 * esa tienda absorbe su propio descuento — coherente con la base de comisión ya acordada
 * (mercancía neta de descuento, sin envío).
 *
 * Consecuencia asumida: un carrito de tres tiendas puede pagar tres envíos.
 */
final class CentralOrderChargesCalculator
{
    public function __construct(
        private readonly CalculateShippingOptionsUseCase $calculateShipping,
        private readonly CalculateTaxUseCase $calculateTax,
        private readonly ValidateCouponUseCase $validateCoupon
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $resolvedItems  Líneas ya resueltas contra el catálogo central.
     * @param  array<string, mixed>  $shippingAddress
     * @param  array<string, string>  $couponsByTenant  tenant_id => código.
     * @return array{
     *     by_tenant: array<string, array{subtotal: float, shipping: float, tax: float, discount: float, coupon_code: string|null, coupon_error: string|null}>,
     *     subtotal: float, shipping: float, tax: float, discount: float
     * }
     */
    public function calculate(array $resolvedItems, array $shippingAddress, array $couponsByTenant = []): array
    {
        $subtotals = [];

        foreach ($resolvedItems as $item) {
            $tenantId = (string) $item['tenant_id'];
            $subtotals[$tenantId] = ($subtotals[$tenantId] ?? 0.0) + ($item['price'] * $item['quantity']);
        }

        $country = $shippingAddress['country'] ?? null;
        $state = $shippingAddress['state'] ?? null;
        $city = $shippingAddress['city'] ?? null;
        $zip = $shippingAddress['zip'] ?? null;

        $byTenant = [];
        $totalShipping = 0.0;
        $totalTax = 0.0;
        $totalDiscount = 0.0;

        foreach ($subtotals as $tenantId => $tenantSubtotal) {
            $tenantSubtotal = round($tenantSubtotal, 2);
            $tenant = Tenant::find($tenantId);

            $shipping = 0.0;
            $tax = 0.0;
            $discount = 0.0;
            $couponCode = $couponsByTenant[$tenantId] ?? null;
            $couponError = null;

            if ($tenant) {
                tenancy()->initialize($tenant);

                try {
                    // El envío es la opción más barata de las que la tienda ofrece para
                    // ese destino. Si no tiene ninguna configurada, sale 0: no se inventa
                    // una tarifa que el comerciante no ha puesto.
                    $opciones = $this->calculateShipping->execute($tenantSubtotal, 0.0, $country, $state, $zip);
                    $shipping = (float) ($opciones->recommendedOption['cost'] ?? 0.0);
                } catch (Throwable) {
                    $shipping = 0.0;
                }

                try {
                    $tax = (float) $this->calculateTax->execute($tenantSubtotal, $country, $state, $city, $zip)->totalTax;
                } catch (Throwable) {
                    $tax = 0.0;
                }

                if ($couponCode !== null && trim($couponCode) !== '') {
                    // El cupón es de ESTA tienda y se valida contra SU subtotal, con la
                    // tenancy inicializada: los cupones viven en la base del inquilino.
                    $resultado = $this->validateCoupon->execute(trim($couponCode), $tenantSubtotal);

                    if ($resultado->isValid) {
                        $discount = $resultado->discountAmount;
                    } else {
                        $couponError = $resultado->message;
                        $couponCode = null;
                    }
                }

                tenancy()->end();
            }

            $byTenant[$tenantId] = [
                'subtotal' => $tenantSubtotal,
                'shipping' => round($shipping, 2),
                'tax' => round($tax, 2),
                'discount' => round(min($discount, $tenantSubtotal), 2),
                'coupon_code' => $couponCode,
                'coupon_error' => $couponError,
            ];

            $totalShipping += $byTenant[$tenantId]['shipping'];
            $totalTax += $byTenant[$tenantId]['tax'];
            $totalDiscount += $byTenant[$tenantId]['discount'];
        }

        return [
            'by_tenant' => $byTenant,
            'subtotal' => round(array_sum($subtotals), 2),
            'shipping' => round($totalShipping, 2),
            'tax' => round($totalTax, 2),
            'discount' => round($totalDiscount, 2),
        ];
    }
}
