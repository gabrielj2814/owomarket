<?php

declare(strict_types=1);

namespace Src\Admin\Application\UseCase;

use Src\Order\Infrastructure\Eloquent\Models\CentralOrder;
use Src\Monetization\Infrastructure\Eloquent\Models\PlatformCommission;
use Exception;

final class ResolveCentralOrderDisputeUseCase
{
    /**
     * @param array{
     *     resolution_type: 'refund'|'cancel',
     *     reason: string,
     *     notes?: string|null
     * } $data
     */
    public function execute(string $orderId, string $adminUserId, array $data): CentralOrder
    {
        $order = CentralOrder::find($orderId);

        if (! $order) {
            throw new Exception("Orden '{$orderId}' no encontrada.", 404);
        }

        $resolutionType = $data['resolution_type'];
        $reason = $data['reason'];

        $metadata = $order->metadata ?? [];
        $metadata['dispute_resolution'] = [
            'resolved_by' => $adminUserId,
            'resolution_type' => $resolutionType,
            'reason' => $reason,
            'notes' => $data['notes'] ?? null,
            'resolved_at' => now()->toIso8601String(),
        ];

        if ($resolutionType === 'refund') {
            $order->status = 'refunded';
            $order->payment_status = 'refunded';
        } else {
            $order->status = 'cancelled';
            $order->payment_status = 'cancelled';
        }

        $order->metadata = $metadata;
        $order->save();

        // Anular comisiones si correspondía
        try {
            PlatformCommission::where('order_id', $order->id)->update([
                'status' => 'cancelled',
            ]);
        } catch (\Throwable $e) {
            // Silently handle
        }

        return $order;
    }
}
