<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Application\UseCases;

use Illuminate\Support\Facades\Schema;
use Src\Coupon\Infrastructure\Eloquent\Models\Coupon;

/**
 * Catálogo de cupones que el portal muestra en «Mis Cupones». Es público y no depende del
 * comprador: la lista es la misma para todo el mundo.
 *
 * Hallazgo C1 — aquí había tres promociones escritas a mano: `OWOPASS10`
 * («10% OFF en tu primera compra»), `ENVIOGRATIS` y `CRYPTO5`. Se anunciaban a todos los
 * compradores y **el checkout las rechazaba las tres**: se comprobó pasando cada código por
 * `ValidateCouponUseCase`, que resuelve contra la tabla `coupons` del inquilino.
 * `OWOPASS10` y `CRYPTO5` no existían en ninguna migración, seeder ni base; `ENVIOGRATIS`
 * sólo en `TenantDemoDataSeeder`, o sea únicamente en tiendas con datos de demostración.
 *
 * Prometer un descuento que no se puede canjear es peor que no ofrecer ninguno: el que se
 * lleva la queja es el comerciante, por una promoción que él nunca creó. Si la sección
 * queda vacía, eso es la verdad — hoy no hay promociones de plataforma.
 *
 * Se quitó también el `$customerId` que recibía y no usaba. Venía de `customer_id` o de la
 * cabecera `X-Customer-Id`, que son exactamente las dos fuentes que
 * `ResolvesAuthenticatedCustomer` documenta como prohibidas. No era un agujero —el valor se
 * descartaba— pero se leía como uno, y era una invitación a «hacerlo funcionar» filtrando
 * por él sin añadir la comprobación de propiedad.
 */
final class ListCustomerAvailableCouponsUseCase
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function execute(): array
    {
        if (! Schema::hasTable('coupons')) {
            return [];
        }

        try {
            $dbCoupons = Coupon::where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('valid_to')->orWhere('valid_to', '>=', now());
                })
                ->get();
        } catch (\Throwable) {
            return [];
        }

        return $dbCoupons->map(fn ($c) => [
            'id' => (string) $c->id,
            'code' => (string) $c->code,
            'title' => (string) ($c->name ?? "Descuento {$c->code}"),
            'description' => (string) ($c->description ?? 'Descuento especial aplicable en checkout.'),
            'discount_type' => (string) ($c->type ?? 'percentage'),
            'discount_value' => (float) $c->value,
            'min_purchase' => (float) ($c->min_order_amount ?? 0),
            'valid_until' => $c->valid_to ? $c->valid_to->format('d/m/Y') : 'Permanente',
            'is_active' => (bool) $c->is_active,
            'badge' => 'Tienda Oficial',
        ])->all();
    }
}
