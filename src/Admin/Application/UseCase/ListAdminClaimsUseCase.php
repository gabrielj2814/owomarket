<?php

declare(strict_types=1);

namespace Src\Admin\Application\UseCase;

use Src\CentralCustomer\Infrastructure\Eloquent\Models\CustomerReturnRequest;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;

/**
 * Las reclamaciones de toda la plataforma, para poder abrir su expediente (subsistema 5, fase D).
 *
 * ## Por qué hace falta
 *
 * `BuildClaimDossierUseCase` pide un `claimId` y **no existía ninguna pantalla central que
 * listara reclamaciones**: el comerciante ve las suyas y el comprador las suyas, pero nadie en
 * la plataforma veía el conjunto. Sin esto el expediente es código inalcanzable, igual que lo
 * era la revisión de KYC antes de tener su ruta.
 *
 * ## El orden
 *
 * Las abiertas primero y lo más viejo arriba, igual que en la pantalla del comerciante: es lo
 * que lleva más tiempo esperando y lo que antes se resolverá solo por silencio.
 *
 * ## Lo que NO devuelve
 *
 * **Ningún dato de identidad**, ni del comerciante ni del comprador. Un listado se consulta
 * muchas veces y casi siempre sin necesitar esos datos; el expediente completo es una segunda
 * petición deliberada, y esa distinción es lo que hace que el dato sensible viaje sólo cuando
 * alguien de verdad va a usarlo.
 */
final class ListAdminClaimsUseCase
{
    private const POR_PAGINA = 15;

    /**
     * @param  array{status?: string|null, search?: string|null, page?: int, per_page?: int}  $filtros
     * @return array{claims: array<int, array<string, mixed>>, pagination: array<string, int>, metrics: array<string, int>}
     */
    public function execute(array $filtros = []): array
    {
        $consulta = CustomerReturnRequest::query();

        $estado = $filtros['status'] ?? null;
        if ($estado === 'open') {
            $consulta->whereIn('status', CustomerReturnRequest::ABIERTAS);
        } elseif ($estado !== null && $estado !== '' && $estado !== 'all') {
            $consulta->where('status', $estado);
        }

        $busqueda = trim((string) ($filtros['search'] ?? ''));
        if ($busqueda !== '') {
            $consulta->where(function ($q) use ($busqueda) {
                $q->where('order_number', 'like', "%{$busqueda}%")
                    ->orWhere('product_name', 'like', "%{$busqueda}%")
                    ->orWhere('customer_email', 'like', "%{$busqueda}%");
            });
        }

        $porPagina = max(1, (int) ($filtros['per_page'] ?? self::POR_PAGINA));
        $pagina = max(1, (int) ($filtros['page'] ?? 1));

        $paginador = $consulta
            ->orderByRaw("CASE WHEN status IN ('requested','in_review') THEN 0 ELSE 1 END")
            ->orderBy('created_at')
            ->paginate(perPage: $porPagina, page: $pagina);

        // Los nombres de tienda en UNA consulta, no una por fila. `customer_return_requests`
        // vive en la base central y `tenants` también, pero no hay relación declarada entre
        // ellas: resolverlo con un bucle sería un N+1 de manual.
        $nombres = Tenant::whereIn('id', collect($paginador->items())->pluck('tenant_id')->unique()->all())
            ->pluck('name', 'id');

        return [
            'claims' => collect($paginador->items())
                ->map(fn (CustomerReturnRequest $r) => [
                    'id' => $r->id,
                    'order_number' => $r->order_number,
                    'product_name' => $r->product_name,
                    'amount' => (float) $r->amount,
                    'tenant_id' => $r->tenant_id,
                    'tenant_name' => $nombres[$r->tenant_id] ?? null,
                    'customer_email' => $r->customer_email,
                    'reason' => $r->reason,
                    'status' => $r->status,
                    'is_open' => $r->isOpen(),
                    'resolved_by' => $r->resolved_by,
                    'platform_covered_amount' => (float) $r->platform_covered_amount,
                    'created_at' => $r->created_at?->toIso8601String(),
                ])
                ->all(),
            'pagination' => [
                'total' => $paginador->total(),
                'current_page' => $paginador->currentPage(),
                'per_page' => $paginador->perPage(),
                'last_page' => $paginador->lastPage(),
            ],
            'metrics' => $this->metricas(),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function metricas(): array
    {
        $porEstado = CustomerReturnRequest::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $abiertas = collect(CustomerReturnRequest::ABIERTAS)
            ->sum(fn (string $e) => (int) ($porEstado[$e] ?? 0));

        return [
            'open_count' => $abiertas,
            // Cuántas resolvió el reloj porque la tienda no contestó. Es la señal que la
            // decisión de garantías pide vigilar, y aquí es donde se ve de un vistazo.
            'timeout_count' => (int) CustomerReturnRequest::where('resolved_by', 'timeout')->count(),
            'total_count' => (int) $porEstado->sum(),
        ];
    }
}
