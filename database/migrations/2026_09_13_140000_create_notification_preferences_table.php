<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Quien quiere recibir tambien por correo (fase 3 de `planes/ESTADO_DEL_PROYECTO.md`).
 *
 * ## Una fila por persona, no una por tipo de aviso
 *
 * La tentacion es «cada aviso con su casilla». Nadie rellena veinte casillas, y un panel de
 * preferencias vacio es el mismo silencio de hoy con mas pantallas. Aqui la pregunta es una:
 * **¿te mando tambien correo?**
 *
 * El dia que alguien pida afinar por tipo, esta tabla admite una columna `types` sin migrar
 * datos: ausencia seguira significando «los criticos».
 *
 * ## Ausencia significa algo
 *
 * Sin fila, se manda correo **solo de lo critico** --lo que tiene un reloj o dinero detras-- y
 * el resto se queda en el buzon. Es el reparto que no obliga a nadie a configurar nada para
 * enterarse de lo que le cuesta dinero, y que no llena de correo a quien no lo pidio.
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

            if ($schema->hasTable('notification_preferences')) {
                continue;
            }

            $schema->create('notification_preferences', function (Blueprint $table) {
                $table->uuid('id')->primary();
                // El mismo alias del mapa de morfismos que usa `notifications`: 'staff' o
                // 'customer'. Ver `NotificationServiceProvider`.
                $table->string('notifiable_type', 40);
                $table->string('notifiable_id');
                $table->boolean('email_enabled')->default(false);
                $table->timestamps();

                // Una sola fila por persona, y es la consulta que se hace al mandar cada aviso.
                $table->unique(['notifiable_type', 'notifiable_id'], 'notif_pref_unica');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->conexiones() as $conexion) {
            Schema::connection($conexion)->dropIfExists('notification_preferences');
        }
    }
};
