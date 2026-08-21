<?php

declare(strict_types=1);

use Illuminate\Session\DatabaseSessionHandler;
use Illuminate\Support\Facades\DB;

/**
 * Hallazgo F1: `sessions.user_id` era NOT NULL con clave foránea a `users`, pero
 * `DatabaseSessionHandler::addUserInformation()` escribe `user_id => auth()->id()`,
 * que es null para cualquier visitante anónimo.
 *
 * Escenario: con `SESSION_DRIVER=database` (lo que trae `.env.example`), la primera
 * petición que persiste sesión —cargar `/auth/login` y generar el token CSRF— reventaba
 * con `SQLSTATE[23000] Column 'user_id' cannot be null`, y nadie podía iniciar sesión.
 */
test('a guest session can be persisted with the database driver', function () {
    $handler = new DatabaseSessionHandler(
        DB::connection(),
        'sessions',
        config('session.lifetime'),
        app()
    );

    $handler->write('sesion-de-visitante-anonimo', 'payload-de-prueba');

    $stored = DB::table('sessions')->where('id', 'sesion-de-visitante-anonimo')->first();

    expect($stored)->not->toBeNull()
        ->and($stored->user_id)->toBeNull();
});
