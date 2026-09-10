<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Plazo de garantia del producto, en dias (subsistema 2 de
 * `planes/anotaciones/DECISION_GARANTIAS_Y_RESPONSABILIDAD.md`).
 *
 * Un solo campo y no dos. La pantalla enseña una casilla «tiene garantia» que revela el
 * input, pero en la tabla eso es un unico dato: `null` es sin garantia y `N` son N dias. Dos
 * columnas para el mismo hecho pueden contradecirse --`has_warranty = true` con los dias en
 * `null`-- y alguien tendria que decidir que significa eso.
 *
 * Columna y no una clave dentro de `specifications`, que ya existe y ya se sincroniza gratis:
 * el fondo de garantia (subsistema 4) va a filtrar y calcular retenciones sobre este valor, y
 * eso es logica de dinero. JSON no se indexa ni se consulta bien.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'warranty_days')) {
                $table->unsignedSmallInteger('warranty_days')->nullable()->after('is_digital');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'warranty_days')) {
                $table->dropColumn('warranty_days');
            }
        });
    }
};
