<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La misma columna en el catalogo central.
 *
 * No es duplicar el dato porque si: `central_products` es la proyeccion del producto en el
 * marketplace, y un pedido central se resuelve contra ella --no contra la base de la tienda--.
 * Si el plazo no viaja hasta aqui, el subsistema 4 no puede saber cuanto retener de una venta
 * del marketplace, que es justo donde la plataforma cobra.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('central_products', function (Blueprint $table) {
            if (! Schema::hasColumn('central_products', 'warranty_days')) {
                $table->unsignedSmallInteger('warranty_days')->nullable()->after('is_featured');
            }
        });
    }

    public function down(): void
    {
        Schema::table('central_products', function (Blueprint $table) {
            if (Schema::hasColumn('central_products', 'warranty_days')) {
                $table->dropColumn('warranty_days');
            }
        });
    }
};
