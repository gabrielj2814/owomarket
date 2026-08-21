<?php

declare(strict_types=1);

namespace Src\CentralMarketplace\Application\Service;

/**
 * Reparte el envío y el descuento del pedido central entre las tiendas que
 * participan en él, en proporción a lo que aporta cada una (hallazgo D1).
 *
 * Antes, `DispatchCentralOrderToTenantsUseCase` creaba el pedido de cada
 * tienda con:
 *
 *     taxAmount: 0.0, shippingAmount: 0.0, discountAmount: 0.0,
 *
 * y usaba el subtotal bruto resultante como (a) importe del registro en
 * `payments` y (b) base de cálculo de la comisión.
 *
 * Escenario numérico de la auditoría: carrito de dos tiendas, A=$60 y B=$40,
 * envío $10, cupón −$30.
 *
 *   - El cliente paga $80 (CentralOrder.total).
 *   - Se creaban pedidos de tienda por $60 y $40 → suma $100 ≠ $80.
 *   - Se registraban `payments` por $100 donde entraron $80.
 *   - Ninguna tienda veía el cupón, y el envío no lo absorbía nadie.
 *
 * Con el prorrateo: A recibe $6 de envío y $18 de descuento (total $48), B
 * recibe $4 y $12 (total $32). Suma exacta: $80.
 *
 * El redondeo se reparte con el método del **resto mayor**: se redondea cada
 * parte a dos decimales y los céntimos que sobran o faltan se asignan a las
 * tiendas con mayor resto pendiente. Así la suma de las partes es SIEMPRE
 * igual al importe original, sin céntimos perdidos ni inventados.
 */
final class CentralOrderProrator
{
    /**
     * @param  array<string, float>  $subtotalsByTenant  tenant_id => subtotal bruto
     * @return array<string, array{shipping: float, discount: float}>
     */
    public function split(array $subtotalsByTenant, float $shippingAmount, float $discountAmount): array
    {
        $shipping = $this->distribute($subtotalsByTenant, round($shippingAmount, 2));
        $discount = $this->distribute($subtotalsByTenant, round($discountAmount, 2));

        $result = [];
        foreach (array_keys($subtotalsByTenant) as $tenantId) {
            $result[$tenantId] = [
                'shipping' => $shipping[$tenantId] ?? 0.0,
                'discount' => $discount[$tenantId] ?? 0.0,
            ];
        }

        return $result;
    }

    /**
     * Reparte $amount entre las claves de $weights en proporción a su peso,
     * garantizando que la suma de las partes sea exactamente $amount.
     *
     * Es público porque también sirve para repartir la comisión entre las
     * líneas de un pedido, no sólo el envío y el descuento entre tiendas.
     *
     * @param  array<string, float>  $weights
     * @return array<string, float>
     */
    public function distribute(array $weights, float $amount): array
    {
        $tenantIds = array_keys($weights);

        if ($tenantIds === [] || abs($amount) < 0.005) {
            return array_fill_keys($tenantIds, 0.0);
        }

        $totalWeight = array_sum($weights);

        // Si todos los subtotales son 0 (caso límite: pedido íntegramente
        // descontado), se reparte por igual en lugar de dividir entre cero.
        if ($totalWeight <= 0) {
            $totalWeight = (float) count($tenantIds);
            $weights = array_fill_keys($tenantIds, 1.0);
        }

        // Se trabaja en céntimos enteros para que el reparto sea exacto.
        $totalCents = (int) round($amount * 100);
        $exact = [];
        $floors = [];
        $assignedCents = 0;

        foreach ($tenantIds as $tenantId) {
            $share = ($weights[$tenantId] / $totalWeight) * $totalCents;
            $exact[$tenantId] = $share;
            $floors[$tenantId] = (int) floor($share);
            $assignedCents += $floors[$tenantId];
        }

        // Los céntimos que quedan sueltos van a quienes tienen mayor resto.
        $remainder = $totalCents - $assignedCents;

        if ($remainder !== 0) {
            $remainders = [];
            foreach ($tenantIds as $tenantId) {
                $remainders[$tenantId] = $exact[$tenantId] - $floors[$tenantId];
            }
            arsort($remainders);

            $step = $remainder > 0 ? 1 : -1;
            $pending = abs($remainder);

            foreach (array_keys($remainders) as $tenantId) {
                if ($pending === 0) {
                    break;
                }
                $floors[$tenantId] += $step;
                $pending--;
            }
        }

        $result = [];
        foreach ($tenantIds as $tenantId) {
            $result[$tenantId] = $floors[$tenantId] / 100;
        }

        return $result;
    }
}
