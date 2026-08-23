<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Hallazgo P0 — ninguna ruta de gobernanza sin rol
|--------------------------------------------------------------------------
|
| P0 no fue que faltara el portero: fue que habia dos puertas al mismo sitio y solo una lo
| tenia. Las acciones sobre tiendas estaban declaradas en `src/Admin/.../web.php` con
| `super_admin` o `staff:manage_tenants`, y OTRA VEZ en `src/Tenant/.../web.php` con solo
| `auth`. Nadie lo vio porque las dos existian y la aplicacion funcionaba.
|
| Este test no comprueba un caso concreto: recorre la tabla de rutas y falla si alguna que
| gobierna tiendas o administradores se queda sin middleware de rol. Es lo unico que
| habria detectado P0 sin que nadie fuera a buscarlo — y tambien el registro duplicado de
| `SupportTicket`, que era la misma patologia.
*/

/**
 * Middlewares que cuentan como «alguien comprobo el rol».
 *
 * `gatherMiddleware()` devuelve los ALIAS tal como se escribieron en la ruta
 * (`staff:manage_tenants`, `own_user`), no las clases resueltas, asi que se buscan los
 * alias. Se aceptan tambien los nombres de clase por si alguna ruta se declara con la
 * clase directamente.
 */
function exigeRol(array $middleware): bool
{
    $texto = implode(' ', $middleware);

    $aceptados = [
        'super_admin', 'staff', 'tenant_owner', 'own_user', 'tenant_can', 'internal',
        'EnsureUserIsSuperAdmin', 'EnsureUserHasStaffPermission', 'EnsureUserIsTenantOwner',
        'EnsureRouteUserIsSelf', 'EnsureTenantUserHasPermission', 'InternalServiceMiddleware',
    ];

    foreach ($aceptados as $marca) {
        if (preg_match('/(^|[\s|])'.preg_quote($marca, '/').'([\s|:]|$)/', $texto)) {
            return true;
        }
    }

    return false;
}

test('ninguna ruta que gobierne tiendas queda sin comprobacion de rol', function () {
    // Rutas que crean, consultan o cambian el estado de una tienda ajena, o que emiten un
    // token para entrar en ella.
    $patrones = ['tenants/', 'backoffice/{id}', '/360'];

    $desprotegidas = collect(Route::getRoutes()->getRoutes())
        ->filter(function ($ruta) use ($patrones) {
            $uri = $ruta->uri();

            // El alta publica de tienda es deliberadamente anonima: es el formulario de
            // registro, y su proteccion es el limite de tasa, no el rol.
            if (str_contains($uri, 'create/account')) {
                return false;
            }

            foreach ($patrones as $patron) {
                if (str_contains($uri, $patron)) {
                    return true;
                }
            }

            return false;
        })
        ->reject(fn ($ruta) => exigeRol($ruta->gatherMiddleware()))
        ->map(fn ($ruta) => $ruta->methods()[0].' '.$ruta->uri())
        ->unique()
        ->values()
        ->all();

    expect($desprotegidas)->toBe([], 'Rutas de gobernanza sin middleware de rol: '.implode(', ', $desprotegidas));
});

test('ninguna ruta de perfil de administrador queda sin comprobacion de rol', function () {
    // Hallazgo P2: tres de las cuatro rutas del bloque «perfil propio» no comprobaban de
    // quien era el perfil.
    $desprotegidas = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($ruta) => str_contains($ruta->uri(), 'admin/backoffice/{user_uuid}/profile'))
        ->reject(fn ($ruta) => exigeRol($ruta->gatherMiddleware()))
        ->map(fn ($ruta) => $ruta->methods()[0].' '.$ruta->uri())
        ->unique()
        ->values()
        ->all();

    expect($desprotegidas)->toBe([], 'Rutas de perfil sin comprobacion: '.implode(', ', $desprotegidas));
});
