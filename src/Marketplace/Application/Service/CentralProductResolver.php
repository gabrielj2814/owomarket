<?php

declare(strict_types=1);

namespace Src\Marketplace\Application\Service;

use Src\Product\Infrastructure\Eloquent\Models\CentralProduct;

/**
 * Resuelve el producto del marketplace central a partir del identificador que viene en la
 * URL (hallazgo E3).
 *
 * El código anterior, repetido en dos controladores, buscaba así:
 *
 *     ->where(fn ($q) => $q->where('slug', $slugOrId)
 *                          ->orWhere('id', $slugOrId)
 *                          ->orWhere('tenant_product_id', $slugOrId))
 *     ->first()
 *
 * Los tres campos competían en el mismo `OR` y **sin filtrar por tienda**. Como el slug se
 * copia tal cual desde cada tienda, los duplicados entre tiendas son la norma: si la
 * tienda A y la B publican `camisa-blanca`, al abrir el producto de B desde el listado
 * central se mostraba la ficha, el precio y la tienda de **A**, y el botón de «añadir al
 * carrito» apuntaba al inquilino equivocado.
 *
 * Aquí se resuelve por prioridad, no por competición:
 *
 *   1. `id` del catálogo central — un UUID, inequívoco.
 *   2. `tenant_product_id` — también un UUID, inequívoco.
 *   3. `slug` — ambiguo por naturaleza en una URL global sin tienda. Se conserva por
 *      compatibilidad con los enlaces ya publicados, pero se desempata siempre por el más
 *      antiguo, de modo que al menos sea **estable**: la misma URL lleva siempre a la
 *      misma ficha, en vez de depender del orden que devuelva la base de datos.
 *
 * Los enlaces del marketplace pasaron a usar el id justo por esto; el slug queda como
 * camino heredado.
 */
final class CentralProductResolver
{
    public function resolveVisible(string $slugOrId): ?CentralProduct
    {
        foreach (['id', 'tenant_product_id'] as $exactColumn) {
            $product = $this->baseQuery()->where($exactColumn, $slugOrId)->first();

            if ($product) {
                return $product;
            }
        }

        return $this->baseQuery()
            ->where('slug', $slugOrId)
            ->orderBy('created_at')
            ->orderBy('id')
            ->first();
    }

    private function baseQuery()
    {
        return CentralProduct::with('tenant.domains')->where('is_visible', true);
    }
}
