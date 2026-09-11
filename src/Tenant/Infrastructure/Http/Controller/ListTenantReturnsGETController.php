<?php

declare(strict_types=1);

namespace Src\Tenant\Infrastructure\Http\Controller;

use Illuminate\Http\JsonResponse;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CustomerReturnRequest;
use Src\Shared\Helper\ApiResponse;
use Src\Tenant\Application\Service\TenantOwnershipVerifier;

/**
 * Las reclamaciones de una tienda, lo mas viejo primero (subsistema 5, fase A).
 *
 * Lo mas viejo primero a proposito: es lo que lleva mas tiempo esperando y lo que antes va a
 * resolverse solo por silencio, con el coste que eso tiene para la tienda.
 */
final class ListTenantReturnsGETController
{
    public function __construct(
        private readonly TenantOwnershipVerifier $ownership
    ) {}

    public function __invoke(string $tenantId): JsonResponse
    {
        $userId = (string) (auth()->id() ?? '');

        if ($userId === '') {
            return ApiResponse::error('Debes iniciar sesión.', 401);
        }

        $this->ownership->ensureOwns($userId, $tenantId);

        $reclamaciones = CustomerReturnRequest::where('tenant_id', $tenantId)
            ->orderByRaw("CASE WHEN status IN ('requested','in_review') THEN 0 ELSE 1 END")
            ->orderBy('created_at')
            ->limit(100)
            ->get()
            ->map(fn (CustomerReturnRequest $r) => [
                'id' => $r->id,
                'order_number' => $r->order_number,
                'product_name' => $r->product_name,
                'amount' => (float) $r->amount,
                'reason' => $r->reason,
                'description' => $r->description,
                'photos' => $r->photos ?? [],
                'status' => $r->status,
                'is_open' => $r->isOpen(),
                'resolved_by' => $r->resolved_by,
                'resolution_notes' => $r->resolution_notes,
                'created_at' => $r->created_at?->toIso8601String(),
            ]);

        return ApiResponse::success($reclamaciones->all(), 'Reclamaciones de la tienda');
    }
}
