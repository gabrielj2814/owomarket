<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Src\User\Infrastructure\Eloquent\Models\User;

/*
|--------------------------------------------------------------------------
| AUDITORIA 22/08 — el perfil de administrador no comprueba de quien es
|--------------------------------------------------------------------------
|
| `/admin/backoffice/{user_uuid}/profile` lleva solo `auth`. El comentario del bloque de
| rutas dice «Perfil PROPIO del administrador», pero nada obliga a que sea el propio: el
| controlador toma el `{user_uuid}` de la URL y nunca lo compara con la sesion.
|
| El arreglo del hallazgo A7 llego a `change-password` —que si resuelve con `auth()->id()`—
| y se salto las otras tres rutas del mismo bloque.
|
| Estos tests se escriben ROJOS a proposito: documentan el agujero antes de taparlo.
*/

function usuarioCentralDeTipo(string $type, string $nombre): User
{
    return User::forceCreate([
        'id' => (string) Str::uuid(),
        'name' => $nombre,
        'email' => Str::slug($nombre).'_'.bin2hex(random_bytes(3)).'@owomarket.local',
        'password' => bcrypt('EndAdmin_12345678'),
        'type' => $type,
        'is_active' => true,
    ]);
}

test('un propietario de tienda no puede leer el perfil de un superadministrador', function () {
    // El caso que importa: un `tenant_owner` tiene sesion en el hub central, asi que pasa
    // el `auth` de la ruta. Lo que ve es el nombre, el CORREO y el telefono del superadmin.
    $superAdmin = usuarioCentralDeTipo('super_admin', 'Super Admin');
    $propietario = usuarioCentralDeTipo('tenant_owner', 'Propietario Curioso');

    $respuesta = $this->actingAs($propietario)
        ->get('/admin/backoffice/'.$superAdmin->id.'/profile');

    expect($respuesta->status())->toBeIn([403, 404]);
});

test('un administrador no puede cambiar el perfil de otro', function () {
    $victima = usuarioCentralDeTipo('super_admin', 'Admin Victima');
    $atacante = usuarioCentralDeTipo('tenant_owner', 'Atacante');

    $respuesta = $this->actingAs($atacante)
        ->putJson('/admin/backoffice/'.$victima->id.'/profile', [
            'name' => 'Nombre Cambiado Por Otro',
            'phone' => '0000000000',
        ]);

    expect($respuesta->status())->toBeIn([403, 404]);

    // Y sobre todo: que no le haya cambiado el nombre.
    expect(User::find($victima->id)->name)->toBe('Admin Victima');
});
