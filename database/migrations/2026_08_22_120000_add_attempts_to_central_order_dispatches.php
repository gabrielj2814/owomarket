<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hallazgo N17: los despachos fallidos quedaban en `status = 'failed'` y nada los
 * reintentaba.
 *
 * Con el reintento en cola hace falta saber cuántas veces se ha probado cada tienda,
 * por dos motivos: para poner un tope —una tienda permanentemente rota no puede
 * reintentarse eternamente— y para que quien mire la tabla distinga «falló una vez y
 * se recuperó» de «lleva cinco intentos sin entrar».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('central_order_dispatches', function (Blueprint $table) {
            $table->unsignedTinyInteger('attempts')->default(0)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('central_order_dispatches', function (Blueprint $table) {
            $table->dropColumn('attempts');
        });
    }
};
