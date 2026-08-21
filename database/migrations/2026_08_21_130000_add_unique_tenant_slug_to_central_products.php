<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hallazgo E3: `central_products` sólo indexaba `slug` sin restricción de unicidad, así
 * que nada impedía que una tienda acabara con dos filas del mismo slug y que la búsqueda
 * por slug devolviera una u otra según el orden de la base de datos.
 *
 * La unicidad es por `(tenant_id, slug)`, no global: dos tiendas distintas **sí** pueden
 * vender `camisa-blanca`, y de hecho es lo normal. Lo que se garantiza aquí es que, fijada
 * la tienda, el slug identifica un único producto — que es lo que hace resoluble la pareja
 * `tenant_id + slug` que pedía la auditoría.
 *
 * El índice antiguo sobre `slug` a secas se conserva: sigue sirviendo para las búsquedas
 * por slug sin tienda del camino heredado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('central_products', function (Blueprint $table) {
            $table->unique(['tenant_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::table('central_products', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'slug']);
        });
    }
};
