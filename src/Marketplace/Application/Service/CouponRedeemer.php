<?php

declare(strict_types=1);

namespace Src\Marketplace\Application\Service;

use Exception;
use Src\Coupon\Infrastructure\Eloquent\Models\Coupon;

/**
 * Consume un uso del cupón de forma segura frente a compras simultáneas (hallazgo C6).
 *
 * El checkout hacía:
 *
 *     $coupon->increment('used_count');
 *
 * `increment()` es atómico a nivel de columna, pero **no comprueba el techo**. La
 * validación previa (leer `used_count`, compararlo con `usage_limit`) ocurría en una
 * sentencia aparte, así que N peticiones paralelas la pasaban todas y el cupón acababa
 * canjeado más veces de las permitidas.
 *
 * Aquí la comprobación y el incremento son **la misma sentencia**, y se mira el número de
 * filas afectadas: si es 0, otro comprador agotó el cupón en el hueco entre validar y
 * canjear, y el pedido se rechaza en vez de aplicar un descuento que ya no correspondía.
 *
 * **Debe invocarse dentro de la transacción del checkout**, igual que `StockReserver`: si
 * el pedido no llega a crearse, el uso del cupón tiene que revertirse con él.
 */
final class CouponRedeemer
{
    /**
     * @throws Exception 409 si el cupón se agotó mientras se procesaba el pedido.
     */
    public function redeem(string $code): void
    {
        $affected = Coupon::query()
            ->where('code', $code)
            ->where('is_active', true)
            ->whereRaw('(usage_limit IS NULL OR used_count < usage_limit)')
            ->increment('used_count');

        if ($affected === 0) {
            throw new Exception(
                sprintf('El cupón «%s» acaba de agotarse y ya no puede aplicarse.', $code),
                409
            );
        }
    }
}
