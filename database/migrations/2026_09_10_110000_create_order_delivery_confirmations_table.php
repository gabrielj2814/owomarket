<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El expediente de entrega de un pedido: quien dijo que salio, quien dijo que llego, y con
 * que pruebas (subsistema 3 de `planes/anotaciones/DECISION_GARANTIAS_Y_RESPONSABILIDAD.md`).
 *
 * **Por que vive en la base CENTRAL y no en la del inquilino.** Los tres actores que tocan
 * este expediente estan en sitios distintos: el comerciante trabaja en la base de su tienda,
 * el comprador y el administrador viven en central, y lo que hay que decidir --liberar el
 * dinero-- tambien es central, porque es `platform_commissions.released_at`. Poniendolo aqui
 * ninguno necesita leer la base del otro.
 *
 * **Por que NO va dentro de `shipments`.** Son cosas distintas y juntarlas es como nace la
 * divergencia que este repositorio ya ha pagado tres veces: `shipments` es la logistica del
 * comerciante --transportista, guia, seguimiento-- y puede haber varios por pedido. Esto es
 * el expediente de entrega y liberacion, y hay exactamente uno por pedido de tienda.
 *
 * **La clave es `(tenant_id, order_id)` y `order_id` es el pedido DE LA TIENDA**, igual que
 * en `platform_commissions.order_id`. Un pedido central que se reparte entre tres tiendas
 * genera tres expedientes: cada tienda entrega lo suyo y el comprador confirma por separado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_delivery_confirmations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tenant_id');
            $table->string('order_id');
            // Para poder enseñarle al comprador este expediente desde su pedido del
            // marketplace. Nulo en las ventas del escaparate, que no pasan por un pedido
            // central.
            $table->uuid('central_order_id')->nullable();
            $table->uuid('customer_id')->nullable();

            // Lo declara el comerciante. **Ya no libera dinero**: solo arranca el reloj.
            $table->timestamp('declared_delivered_at')->nullable();
            // Lo declara el comprador. Esto SI libera.
            $table->timestamp('confirmed_at')->nullable();
            // Como se libero, para poder distinguir despues una entrega confirmada de una
            // que solo vencio el plazo. Sin esto no se puede medir cuantos compradores
            // confirman de verdad, que es lo que dira si los 7 dias son los correctos.
            $table->string('released_by', 20)->nullable(); // 'customer' | 'timeout'
            $table->timestamp('released_at')->nullable();

            // Fase B. Nacen aqui porque la tabla se esta creando igualmente: dos columnas
            // nulables ahora cuestan dos lineas y una migracion aparte cuesta treinta.
            $table->json('shipment_evidence')->nullable();
            $table->json('confirmation_evidence')->nullable();

            $table->timestamps();

            // Un expediente por pedido de tienda. Es la garantia de que dos confirmaciones
            // simultaneas no crean dos filas y liberan dos veces.
            $table->unique(['tenant_id', 'order_id']);
            $table->index('order_id');
            $table->index('customer_id');
            // Para el comando que libera por silencio: busca lo declarado y sin confirmar.
            //
            // Con nombre explicito porque el que genera Laravel
            // --`order_delivery_confirmations_declared_delivered_at_released_at_index`--
            // pasa de los 64 caracteres que admite MySQL. Y como el DDL no es transaccional,
            // ese fallo deja la tabla creada a medias y la migracion sin registrar: el
            // siguiente intento choca con «Table already exists» y esconde el error real.
            $table->index(['declared_delivered_at', 'released_at'], 'odc_pendientes_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_delivery_confirmations');
    }
};
