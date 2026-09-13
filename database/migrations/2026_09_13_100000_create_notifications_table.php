<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El buzon de las tres audiencias (fase 1 de `planes/ESTADO_DEL_PROYECTO.md`).
 *
 * Es la tabla que Laravel crea con `notifications:table`, que en este proyecto **nunca se
 * genero**: hasta hoy no habia modulo de notificaciones y los tres unicos correos salian
 * directos con `Mail::to()`.
 *
 * ## Por que UNA tabla y en la base central
 *
 * Era el riesgo grande de un sistema multi-inquilino, y resulta que aqui no existe: las tres
 * audiencias viven en la base central. El administrador y el personal de tienda son filas de
 * `users` --el pivote `tenant_users` dice a que tienda pertenece cada uno-- y el comprador es
 * `central_customers`.
 *
 * El `Customer` de la base del inquilino **no es destinatario de nada**: es el registro
 * comercial de esa tienda, y el SSO lo enlaza con su cuenta central por `central_uuid`. El
 * destinatario siempre es la cuenta central.
 *
 * ## El indice no es opcional
 *
 * La consulta del buzon es siempre la misma --«los mios, sin leer, los ultimos primero»-- y la
 * cabecera la hace en cada carga de pagina para pintar el contador. Sin indice eso es un barrido
 * de la tabla entera en el sitio mas visitado de la aplicacion.
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

            if ($schema->hasTable('notifications')) {
                continue;
            }

            $schema->create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                /*
                 * Un alias corto ('staff', 'customer'), no el nombre de la clase.
                 *
                 * Hay CUATRO clases `User` distintas sobre la tabla `users` en este
                 * repositorio. Con el morfismo por defecto, la misma persona acumularia
                 * avisos bajo cuatro tipos y una consulta filtrada por uno se dejaria los
                 * otros tres fuera **sin dar ningun error**: simplemente faltarian avisos.
                 *
                 * El mapa vive en `NotificationServiceProvider`. De rebote, mover o renombrar
                 * una clase deja de romper las filas ya guardadas.
                 */
                $table->string('notifiable_type', 40);
                $table->string('notifiable_id');
                $table->string('type');
                $table->json('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();

                // La consulta del buzon y la del contador de no leidas, que corre en cada
                // carga de pagina.
                $table->index(['notifiable_type', 'notifiable_id', 'read_at'], 'notif_buzon_index');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->conexiones() as $conexion) {
            Schema::connection($conexion)->dropIfExists('notifications');
        }
    }
};
