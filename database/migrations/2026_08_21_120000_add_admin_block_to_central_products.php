<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hallazgos E1 y E2: al pasar la sincronización del catálogo central a eventos de modelo,
 * `central_products.is_visible` pasa a derivarse de la visibilidad del producto en la
 * tienda. Eso, por sí solo, dejaría al comerciante **deshaciendo una decisión del
 * moderador**: bastaba con guardar el producto para volver a publicar algo que el
 * superadmin había retirado del marketplace.
 *
 * Esta bandera separa las dos decisiones: el comerciante controla `is_visible` de su
 * producto, y el moderador puede vetarlo por encima.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('central_products', function (Blueprint $table) {
            $table->boolean('is_blocked_by_admin')->default(false)->after('is_visible');
        });
    }

    public function down(): void
    {
        Schema::table('central_products', function (Blueprint $table) {
            $table->dropColumn('is_blocked_by_admin');
        });
    }
};
