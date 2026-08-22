<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hallazgo N36: el marketplace central no podía vender por variante.
 *
 * `central_order_items` no guardaba cuál se compró, así que pasaban dos cosas a la vez:
 *
 *   1. El comprador no elegía talla ni color, y el comerciante recibía un pedido sin
 *      saber qué meter en la caja.
 *   2. La reserva de stock de la Fase 6.1 descontaba del producto padre, cuyo `quantity`
 *      **nadie mantiene** en un producto con variantes: `StockReserver` sólo descuenta de
 *      la variante cuando se le pasa una, así que ese número se queda como estaba el día
 *      de la siembra. Vender por el marketplace descuadraba padre y variantes.
 *
 * La columna es `nullable` a propósito: los productos sin variantes son la mayoría y
 * siguen viajando sin ella, igual que en `order_items` del inquilino.
 *
 * No lleva clave foránea porque las variantes viven en la base de datos de cada tienda y
 * esta tabla está en la central. La integridad la valida `CentralItemPriceResolver`
 * contra el catálogo sincronizado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('central_order_items', function (Blueprint $table) {
            $table->string('variant_id')->nullable()->after('product_id');
            $table->index('variant_id');
        });
    }

    public function down(): void
    {
        Schema::table('central_order_items', function (Blueprint $table) {
            $table->dropIndex(['variant_id']);
            $table->dropColumn('variant_id');
        });
    }
};
