<?php

declare(strict_types=1);

namespace Src\Tenant\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\CentralCustomer\Application\Service\ClaimResponseWindow;
use Src\Tenant\Application\Service\TenantOwnershipVerifier;

/**
 * La pantalla donde el comerciante responde a una reclamación (subsistema 5, vista 2).
 *
 * **Hasta que existió, el reloj aprobaba todo por silencio.** `returns:auto-resolve` corre a
 * diario y resuelve a favor del comprador lo que la tienda no responde en el plazo; como no
 * había ninguna pantalla donde responder, toda reclamación se aprobaba sola y revertía la
 * venta. El comerciante ni siquiera sabía que existía.
 *
 * Las reclamaciones se piden por API y no se pasan ya renderizadas: la pantalla necesita
 * refrescar tras resolver una --el reloj puede haberse adelantado-- y tener el mismo camino
 * para la carga inicial y para el refresco evita que los dos digan cosas distintas.
 */
final class ViewTenantOwnerReturnsGETController extends Controller
{
    public function __construct(
        private readonly TenantOwnershipVerifier $ownership,
        private readonly ClaimResponseWindow $plazo
    ) {}

    public function __invoke(Request $request, string $user_uuid): Response
    {
        $tiendas = $this->ownership->tenantsOf($user_uuid)
            ->map(fn ($t) => ['id' => (string) $t->id, 'name' => (string) $t->name])
            ->values()
            ->all();

        return Inertia::render('tenant/returns/TenantReturnsPage', [
            'title' => 'Reclamaciones de mis tiendas - OwOMarket',
            'user_id' => $user_uuid,
            'tenants' => $tiendas,
            // Para que la pantalla pueda explicar la regla y no solo mostrar el número de días
            // que queda en cada caso: «tienes N días» se entiende mejor sabiendo cuál es el
            // plazo completo.
            'response_days' => $this->plazo->days(),
        ]);
    }
}
