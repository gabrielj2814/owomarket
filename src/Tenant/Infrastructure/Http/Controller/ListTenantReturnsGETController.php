<?php

declare(strict_types=1);

namespace Src\Tenant\Infrastructure\Http\Controller;

use Illuminate\Http\JsonResponse;
use Src\CentralCustomer\Application\Service\ClaimResponseWindow;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CustomerReturnRequest;
use Src\Shared\Helper\ApiResponse;
use Src\Tenant\Application\Service\TenantOwnershipVerifier;

/**
 * Las reclamaciones de una tienda, lo mas viejo primero (subsistema 5, fase A).
 *
 * Lo mas viejo primero a proposito: es lo que lleva mas tiempo esperando y lo que antes va a
 * resolverse solo por silencio, con el coste que eso tiene para la tienda.
 *
 * ## El reloj viaja con cada reclamacion
 *
 * `days_left` y `deadline_at` salen de aqui y no de la pantalla. El plazo depende de un ajuste
 * central (`central_claim_response_days`) que el navegador no ve, asi que una cuenta atras
 * calculada en el frontend seria una invencion. Ademas es la convencion de la casa: cuando el
 * backend ya sabe algo, no se deja que la pantalla llegue a su propia conclusion.
 *
 * Ese dato es lo que hace que la pantalla se use. Sin el, el comerciante no sabe que tiene un
 * plazo corriendo, y la reclamacion se resuelve sola a favor del comprador --revirtiendo la
 * venta y bajando su nivel de reputacion a bajo, que le duplica la retencion de TODAS sus
 * ventas--.
 */
final class ListTenantReturnsGETController
{
    public function __construct(
        private readonly TenantOwnershipVerifier $ownership,
        private readonly ClaimResponseWindow $plazo
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
                // Solo para las abiertas: en una ya resuelta una cuenta atras no significa
                // nada y solo confunde sobre si aun se puede hacer algo.
                'days_left' => $r->isOpen() && $r->created_at !== null
                    ? $this->plazo->daysLeftFrom($r->created_at)
                    : null,
                'deadline_at' => $r->isOpen() && $r->created_at !== null
                    ? $this->plazo->deadlineFor($r->created_at)->toIso8601String()
                    : null,
                'resolved_by' => $r->resolved_by,
                'resolution_notes' => $r->resolution_notes,
                'created_at' => $r->created_at?->toIso8601String(),
            ]);

        return ApiResponse::success($reclamaciones->all(), 'Reclamaciones de la tienda');
    }
}
