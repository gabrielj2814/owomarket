<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Application\UseCases;

use App\Models\CentralOrder;
use Exception;

final class GetCustomerOrderTrackingUseCase
{
    /**
     * @return array{order_id: string, order_number: string, status: string, current_step: int, courier?: string|null, tracking_number?: string|null, tracking_url?: string|null, timeline: array<int, array<string, mixed>>}
     */
    public function execute(string $customerId, string $orderId): array
    {
        $order = CentralOrder::where('id', $orderId)
            ->where('customer_id', $customerId)
            ->first();

        if (! $order) {
            throw new Exception('Pedido no encontrado para consultar seguimiento.', 404);
        }

        $meta = $order->metadata ?? [];
        $courier = $meta['courier'] ?? $meta['courier_name'] ?? 'MRW / Zoom';
        $trackingNumber = $meta['tracking_number'] ?? $meta['guide_number'] ?? null;
        $trackingUrl = $meta['tracking_url'] ?? ($trackingNumber ? "https://tracking.owomarket.com?guide={$trackingNumber}" : null);

        // Mapeo de paso actual:
        // 1: Registrado, 2: Pago Confirmado, 3: En Preparación, 4: En Camino, 5: Entregado
        $currentStep = match ($order->status) {
            'pending' => $order->payment_status === 'paid' ? 2 : 1,
            'paid' => 2,
            'processing' => 3,
            'completed' => 5,
            'cancelled' => 0,
            default => 2,
        };

        if ($trackingNumber && $order->status !== 'completed' && $order->status !== 'cancelled') {
            $currentStep = 4; // Despachado
        }

        $timeline = [
            [
                'step' => 1,
                'key' => 'placed',
                'title' => 'Pedido Registrado',
                'description' => 'Tu orden ha sido registrada en el sistema.',
                'timestamp' => $order->created_at?->toIso8601String(),
                'is_completed' => true,
                'is_current' => $currentStep === 1,
            ],
            [
                'step' => 2,
                'key' => 'paid',
                'title' => 'Pago Confirmado',
                'description' => $order->payment_status === 'paid' ? 'Pago verificado exitosamente.' : 'En espera de conciliación del pago.',
                'timestamp' => $order->payment_status === 'paid' ? $order->updated_at?->toIso8601String() : null,
                'is_completed' => $currentStep >= 2,
                'is_current' => $currentStep === 2,
            ],
            [
                'step' => 3,
                'key' => 'processing',
                'title' => 'En Preparación',
                'description' => 'Las tiendas están empacando tus productos.',
                'timestamp' => $currentStep >= 3 ? $order->updated_at?->toIso8601String() : null,
                'is_completed' => $currentStep >= 3,
                'is_current' => $currentStep === 3,
            ],
            [
                'step' => 4,
                'key' => 'in_transit',
                'title' => 'En Camino / Despachado',
                'description' => $trackingNumber ? "Asignado a {$courier} con Guía: {$trackingNumber}" : 'Pendiente de recolección por encomienda.',
                'timestamp' => $currentStep >= 4 ? $order->updated_at?->toIso8601String() : null,
                'is_completed' => $currentStep >= 4,
                'is_current' => $currentStep === 4,
            ],
            [
                'step' => 5,
                'key' => 'delivered',
                'title' => 'Entregado',
                'description' => 'Pedido entregado en la dirección de destino.',
                'timestamp' => $currentStep === 5 ? $order->updated_at?->toIso8601String() : null,
                'is_completed' => $currentStep === 5,
                'is_current' => $currentStep === 5,
            ],
        ];

        return [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'current_step' => $currentStep,
            'courier' => $courier,
            'tracking_number' => $trackingNumber,
            'tracking_url' => $trackingUrl,
            'timeline' => $timeline,
        ];
    }
}
