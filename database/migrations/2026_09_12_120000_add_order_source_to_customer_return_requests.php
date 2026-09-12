<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * De donde viene el pedido que se reclama (subsistema 5, fase 2 del escaparate).
 *
 * Hasta hoy `order_id` siempre apuntaba a un `central_orders.id`. Al abrir la reclamacion al
 * comprador de una tienda pasa a poder apuntar a un `orders.id` de la base del inquilino, y
 * **sin esta columna nada distinguiria una de otra**: los dos son cadenas con forma de UUID.
 *
 * La consecuencia no seria teorica. En `/account/returns` la reclamacion de escaparate
 * aparece igual que las demas --el listado filtra por `customer_id`, que es el central-- y su
 * enlace al pedido llevaria a un pedido central que no existe.
 *
 * `central` por defecto: las filas que ya existen son todas centrales, asi que quedan bien
 * sin rellenado manual.
 */
return new class extends Migration
{
    /** @return array<int, string> */
    private function conexiones(): array
    {
        $enTests = app()->runningUnitTests() || app()->environment('testing');

        return array_unique(array_filter([
            config('database.default'),
            (! $enTests && config('database.connections.central')) ? 'central' : null,
        ]));
    }

    public function up(): void
    {
        foreach ($this->conexiones() as $conexion) {
            $schema = Schema::connection($conexion);

            if (! $schema->hasTable('customer_return_requests')) {
                continue;
            }
            if ($schema->hasColumn('customer_return_requests', 'order_source')) {
                continue;
            }

            $schema->table('customer_return_requests', function (Blueprint $table) {
                $table->string('order_source', 20)->default('central')->after('order_id');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->conexiones() as $conexion) {
            $schema = Schema::connection($conexion);

            if ($schema->hasColumn('customer_return_requests', 'order_source')) {
                $schema->table('customer_return_requests', function (Blueprint $table) {
                    $table->dropColumn('order_source');
                });
            }
        }
    }
};
