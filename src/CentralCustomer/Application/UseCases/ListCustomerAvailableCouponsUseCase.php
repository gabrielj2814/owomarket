<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Application\UseCases;

use Illuminate\Support\Facades\Schema;
use Src\Coupon\Infrastructure\Eloquent\Models\Coupon;

final class ListCustomerAvailableCouponsUseCase
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function execute(string $customerId): array
    {
        $coupons = [
            [
                'id' => 'promo_owopass_10',
                'code' => 'OWOPASS10',
                'title' => '10% OFF en tu primera compra con OwO Pass',
                'description' => 'Aplica para compras en cualquier tienda oficial de OwOMarket con Pago Móvil o Binance Pay.',
                'discount_type' => 'percentage',
                'discount_value' => 10,
                'min_purchase' => 20.00,
                'valid_until' => now()->addMonths(3)->format('d/m/Y'),
                'is_active' => true,
                'badge' => 'OwO Pass Exclusivo',
            ],
            [
                'id' => 'promo_envio_gratis',
                'code' => 'ENVIOGRATIS',
                'title' => 'Envío Gratis Nacional (MRW / Zoom)',
                'description' => 'Descuento del 100% en costo de despacho en compras superiores a $50.',
                'discount_type' => 'fixed',
                'discount_value' => 5.00,
                'min_purchase' => 50.00,
                'valid_until' => now()->addMonths(2)->format('d/m/Y'),
                'is_active' => true,
                'badge' => 'Promoción Activa',
            ],
            [
                'id' => 'promo_binance_pay',
                'code' => 'CRYPTO5',
                'title' => '5% Cashback en USDT con Binance Pay',
                'description' => 'Ahorra 5% directo pagando con Binance Pay sin comisiones adicionales.',
                'discount_type' => 'percentage',
                'discount_value' => 5,
                'min_purchase' => 15.00,
                'valid_until' => now()->addMonths(6)->format('d/m/Y'),
                'is_active' => true,
                'badge' => 'Binance Pay',
            ],
        ];

        // Si existen cupones dinámicos en la base de datos, incorporarlos
        if (Schema::hasTable('coupons')) {
            try {
                $dbCoupons = Coupon::where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('valid_to')->orWhere('valid_to', '>=', now());
                    })
                    ->get();

                foreach ($dbCoupons as $c) {
                    $coupons[] = [
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
                    ];
                }
            } catch (\Throwable) {
                // Mantener cupones por defecto
            }
        }

        return $coupons;
    }
}
