<?php

declare(strict_types=1);

namespace Src\Admin\Application\UseCase;

use Src\Monetization\Infrastructure\Eloquent\Models\PlatformCommission;

/**
 * Los cobros que la plataforma todavía no ha confirmado, de todas las tiendas.
 *
 * Sale de `platform_commissions` en `awaiting_payment`, que es la única proyección central que
 * ya existe de una venta. Ver `ConfirmStorefrontPaymentUseCase` para por qué no hay tabla nueva
 * ni se recorren las bases de los inquilinos.
 */
final class ListPendingStorefrontPaymentsUseCase
{
    /**
     * @param  array{tenant_id?: string|null, search?: string|null, per_page?: int, page?: int}  $filtros
     * @return array<string, mixed>
     */
    public function execute(array $filtros = []): array
    {
        $query = PlatformCommission::query()
            ->with('tenant:id,name')
            ->where('status', 'awaiting_payment')
            ->orderBy('created_at', 'asc');   // lo más viejo primero: es lo que lleva más esperando

        if (! empty($filtros['tenant_id'])) {
            $query->where('tenant_id', $filtros['tenant_id']);
        }

        if (! empty($filtros['search'])) {
            $termino = trim((string) $filtros['search']);
            $query->where(function ($q) use ($termino) {
                $q->where('order_number', 'like', "%{$termino}%")
                    ->orWhere('order_id', 'like', "%{$termino}%");
            });
        }

        $paginador = $query->paginate(
            perPage: (int) ($filtros['per_page'] ?? 20),
            page: (int) ($filtros['page'] ?? 1)
        );

        return [
            'payments' => [
                'data' => collect($paginador->items())->map(fn (PlatformCommission $c) => [
                    'id' => $c->id,
                    'tenant_id' => $c->tenant_id,
                    'tenant_name' => $c->tenant?->name,
                    'order_id' => $c->order_id,
                    'order_number' => $c->order_number,
                    'order_total' => (float) $c->order_total,
                    'commission_amount' => (float) $c->commission_amount,
                    'currency' => $c->currency,
                    'exchange_rate' => $c->exchange_rate !== null ? (float) $c->exchange_rate : null,
                    // El importe en bolívares es lo que el administrador coteja contra su
                    // extracto: el comprador pagó bolívares, no dólares.
                    'total_ves' => $c->exchange_rate !== null
                        ? round((float) $c->order_total * (float) $c->exchange_rate, 2)
                        : null,
                    'payment_gateway' => $c->payment_gateway,
                    // La referencia que puso el comprador en el checkout. Es la que hay que
                    // buscar en el extracto.
                    'payment_reference' => $c->metadata['payment_reference'] ?? null,
                    // La que el comerciante haya reportado aparte, si el comprador se la pasó
                    // por otro canal. Es una pista, no un hecho.
                    'reported_reference' => $c->metadata['reported_reference']['reference'] ?? null,
                    'source' => $c->metadata['source'] ?? ($c->central_order_id !== null ? 'central_marketplace' : null),
                    'created_at' => $c->created_at?->toIso8601String(),
                ])->all(),
                'current_page' => $paginador->currentPage(),
                'last_page' => $paginador->lastPage(),
                'total' => $paginador->total(),
                'per_page' => $paginador->perPage(),
            ],
            'metrics' => [
                'pending_count' => PlatformCommission::where('status', 'awaiting_payment')->count(),
                'pending_ves' => (float) PlatformCommission::query()
                    ->where('status', 'awaiting_payment')
                    ->whereNotNull('exchange_rate')
                    ->sum(\Illuminate\Support\Facades\DB::raw('order_total * exchange_rate')),
            ],
        ];
    }
}
