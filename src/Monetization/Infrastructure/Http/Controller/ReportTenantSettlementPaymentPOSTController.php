<?php

declare(strict_types=1);

namespace Src\Monetization\Infrastructure\Http\Controller;

use App\Models\CommissionSettlement;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Shared\Helper\ApiResponse;

final class ReportTenantSettlementPaymentPOSTController
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'settlement_id' => 'required|string',
            'payment_method' => 'required|string',
            'payment_reference' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        $tenantId = tenant('id') ?? (string) $request->input('tenant_id');

        try {
            $settlement = CommissionSettlement::where('id', $request->input('settlement_id'))
                ->where('tenant_id', $tenantId)
                ->first();

            if (! $settlement) {
                throw new Exception('Liquidación no encontrada para esta tienda.', 404);
            }

            $settlement->payment_method = (string) $request->input('payment_method');
            $settlement->payment_reference = (string) $request->input('payment_reference');
            $metadata = $settlement->metadata ?? [];
            $metadata['payment_reported_at'] = now()->toIso8601String();
            $metadata['payment_reported_notes'] = $request->input('notes');
            $settlement->metadata = $metadata;
            $settlement->save();

            return ApiResponse::success(
                data: $settlement,
                message: 'Comprobante de pago de comisiones reportado exitosamente. Pendiente de verificación por SuperAdmin.'
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                code: (int) ($e->getCode() ?: 400)
            );
        }
    }
}
