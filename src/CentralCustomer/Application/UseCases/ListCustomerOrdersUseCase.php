<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Application\UseCases;

use Src\Order\Infrastructure\Eloquent\Models\CentralOrder;
use Src\Product\Infrastructure\Eloquent\Models\CentralProduct;
use Throwable;

final class ListCustomerOrdersUseCase
{
    /**
     * @param  array{status?: string|null, search?: string|null, limit?: int|null, page?: int|null}  $filters
     * @return array{data: array<int, mixed>, total: int, current_page: int, last_page: int}
     */
    public function execute(string $customerId, ?string $customerEmail = null, array $filters = []): array
    {
        $query = CentralOrder::with('items')
            ->where(function ($q) use ($customerId, $customerEmail) {
                $q->where('customer_id', $customerId);
                if ($customerEmail) {
                    $q->orWhere('customer_email', strtolower(trim($customerEmail)));
                }
            });

        if (! empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $search = '%'.trim($filters['search']).'%';
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', $search)
                    ->orWhereHas('items', function ($itemQuery) use ($search) {
                        $itemQuery->where('product_name', 'like', $search);
                    });
            });
        }

        $limit = isset($filters['limit']) && $filters['limit'] > 0 ? (int) $filters['limit'] : 10;
        $paginator = $query->orderBy('created_at', 'desc')->paginate($limit);

        // Hallazgo G15: los items no traian ni el nombre de la tienda ni el slug del
        // producto, asi que «volver a pedir» reconstruia el carrito con
        // `tenant_name: item.tenant_id` y `slug: item.product_id`: el cajon mostraba el
        // UUID de la tienda como si fuera su nombre y el enlace al producto no llevaba a
        // ninguna parte. El frontend no tenia nada mejor con que rellenarlos.
        $orders = $paginator->items();

        foreach ($orders as $order) {
            $order->loadMissing(['items.tenant']);
        }

        $this->attachCatalogSlugs($orders);

        return [
            'data' => $paginator->items(),
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
        ];
    }

    /**
     * Rellena `tenant_name` y `product_slug` en los items de los pedidos.
     *
     * La busqueda de slugs va en **una sola consulta** para todos los items de la pagina:
     * hacerla item a item seria un N+1 sobre la base central en cada carga del historial.
     *
     * El slug es informacion de conveniencia para el enlace del carrito, no del pedido en
     * si: si el catalogo central no responde, el historial se sigue mostrando sin el.
     *
     * @param  array<int, mixed>  $orders
     */
    private function attachCatalogSlugs(array $orders): void
    {
        $productIds = [];

        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $item->tenant_name = $item->tenant?->name;
                $productIds[] = (string) $item->product_id;
            }
        }

        if ($productIds === []) {
            return;
        }

        try {
            $slugs = CentralProduct::query()
                ->whereIn('tenant_product_id', array_unique($productIds))
                ->pluck('slug', 'tenant_product_id');
        } catch (Throwable) {
            return;
        }

        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $item->product_slug = $slugs[(string) $item->product_id] ?? null;
            }
        }
    }
}
