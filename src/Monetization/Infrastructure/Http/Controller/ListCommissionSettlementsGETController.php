<?php

declare(strict_types=1);

namespace Src\Monetization\Infrastructure\Http\Controller;

use Src\Monetization\Infrastructure\Eloquent\Models\CommissionSettlement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Shared\Helper\ApiResponse;

final class ListCommissionSettlementsGETController
{
    public function __invoke(Request $request): JsonResponse
    {
        $query = CommissionSettlement::with(['tenant', 'commissions']);

        if ($request->has('tenant_id') && ! empty($request->input('tenant_id'))) {
            $query->where('tenant_id', $request->input('tenant_id'));
        }

        if ($request->has('status') && ! empty($request->input('status'))) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('type') && ! empty($request->input('type'))) {
            $query->where('type', $request->input('type'));
        }

        $settlements = $query->orderBy('created_at', 'desc')->paginate((int) $request->input('per_page', 15));

        return ApiResponse::success(
            data: $settlements,
            message: 'Liquidaciones de comisiones obtenidas exitosamente'
        );
    }
}
