<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Src\Authentication\Infrastructure\Eloquent\Models\AuthUser;
use Src\Tenant\Infrastructure\Eloquent\Models\User;

/**
 * El endpoint lee de `auth_users`, una proyeccion de `users`, asi que el escenario necesita
 * las dos filas. La primera version de este test solo creaba el `User` y el endpoint
 * devolvia 500 para TODOS los uuid — parecia que no habia fuga y era el fixture.
 */
function crearUsuarioConAuth(string $nombre, string $correo, string $tipo): User
{
    $usuario = User::create([
        'id' => (string) Str::uuid(),
        'name' => $nombre,
        'email' => $correo,
        'password' => bcrypt('OwO_12345678'),
        'type' => $tipo,
        'is_active' => true,
    ]);

    AuthUser::create([
        'id' => (string) Str::uuid(),
        'user_id' => $usuario->id,
        'user_name' => $nombre,
        'user_email' => $correo,
        'user_type' => $tipo,
    ]);

    return $usuario;
}

/*
|--------------------------------------------------------------------------
| Hallazgo T7 - /auth/user/{uuid} devolvia el perfil de cualquiera
|--------------------------------------------------------------------------
|
| Salio del barrido de gemelos: CurrentUserGETController esta expuesto DOS veces con
| guardas distintas.
|
|   api/auth/interna/user/{uuid}   InternalServiceMiddleware   <- servicio a servicio
|   auth/user/{user_uuid}          Authenticate                <- cara al usuario
|
| El controlador toma el uuid de la URL y NO lo compara nunca con la sesion. Para el
| endpoint interno eso es correcto: su trabajo es consultar a cualquiera. Para el otro no,
| y la semantica del interno se colo en el de usuario.
|
| Resultado: cualquiera con sesion en el hub central podia leer el nombre, el correo y el
| ROL de cualquier otro usuario cambiando el uuid de la barra de direcciones. La tabla
| `users` son el personal, los administradores y los propietarios de tienda, asi que un
| comerciante corriente podia enumerar a los administradores de la plataforma con sus
| correos — que es justo el inventario que se necesita antes de una campana de phishing.
|
| Es el hallazgo P1 otra vez: «sus controladores pasan el {user_uuid} de la URL al caso de
| uso sin compararlo nunca con la sesion».
*/
test('un usuario no puede leer el perfil de otro por la URL (T7)', function () {
    $victima = crearUsuarioConAuth('Super Admin Victima', 'victima_'.bin2hex(random_bytes(3)).'@owomarket.local', 'super_admin');
    $curioso = crearUsuarioConAuth('Comerciante Curioso', 'curioso_'.bin2hex(random_bytes(3)).'@example.com', 'tenant_owner');

    $respuesta = $this->actingAs($curioso)->getJson("/auth/user/{$victima->id}");

    expect($respuesta->status())->toBe(403);

    // Y sobre todo: el correo del administrador no viaja en la respuesta.
    expect($respuesta->getContent())->not->toContain($victima->email);
});

test('un usuario si puede leer su propio perfil (T7)', function () {
    $usuario = crearUsuarioConAuth('Propietario Legitimo', 'propio_'.bin2hex(random_bytes(3)).'@example.com', 'tenant_owner');

    // El acceso legitimo tiene que seguir funcionando: cerrar la puerta no puede dejar
    // fuera a quien pregunta por si mismo.
    $this->actingAs($usuario)
        ->getJson("/auth/user/{$usuario->id}")
        ->assertStatus(200)
        ->assertJsonPath('data.user_email', $usuario->email);
});
