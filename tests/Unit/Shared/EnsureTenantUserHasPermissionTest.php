<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Src\Shared\Infrastructure\Http\Middleware\EnsureTenantUserHasPermission;
use Symfony\Component\HttpFoundation\Response;

/*
| Complemento del test de integración (tests/Feature/Tenant/TenantRolePermissionsTest.php).
| Allí el tipo literal `staff` no se puede crear, porque la suite comparte la tabla `users`
| central y su enum no lo admite. Aquí el usuario es un doble, así que sí se puede
| comprobar la cadena exacta que llevan los usuarios dentro de una base de inquilino.
*/

uses(Tests\TestCase::class);

/** Usuario de mentira con el tipo y los permisos que pida cada caso. */
function usuarioDeTienda(string $type, bool $tienePermiso = false): object
{
    return new class($type, $tienePermiso)
    {
        public function __construct(public string $type, private bool $tienePermiso) {}

        public function hasAnyPermission(array $permisos): bool
        {
            return $this->tienePermiso;
        }
    };
}

function pasaPor(string $metodo, ?object $user, string ...$permisos): Response
{
    $request = Request::create('/api-tenant/product/abc', $metodo);
    $request->headers->set('Accept', 'application/json');
    $request->setUserResolver(fn () => $user);

    return (new EnsureTenantUserHasPermission)->handle(
        $request,
        fn () => new Response('ok', 200),
        ...$permisos
    );
}

test('un staff sin permisos no puede escribir', function () {
    expect(pasaPor('DELETE', usuarioDeTienda('staff'), 'manage_catalog')->getStatusCode())->toBe(403);
});

test('un staff con el permiso concedido sí puede escribir', function () {
    expect(pasaPor('DELETE', usuarioDeTienda('staff', true), 'manage_catalog')->getStatusCode())->toBe(200);
});

test('un staff siempre puede leer', function () {
    expect(pasaPor('GET', usuarioDeTienda('staff'), 'manage_catalog')->getStatusCode())->toBe(200);
});

test('el preflight de CORS no se bloquea', function () {
    // Responder 403 al OPTIONS rompe la petición real que viene detrás.
    expect(pasaPor('OPTIONS', usuarioDeTienda('staff'), 'manage_catalog')->getStatusCode())->toBe(200);
});

test('el propietario de la tienda pasa sin permisos', function () {
    expect(pasaPor('DELETE', usuarioDeTienda('owner'), 'manage_catalog')->getStatusCode())->toBe(200);
});

test('el propietario que llega por SSO desde el hub también pasa', function () {
    // Trae el `tenant_owner` de la base central, no el `owner` de la tienda.
    expect(pasaPor('DELETE', usuarioDeTienda('tenant_owner'), 'manage_catalog')->getStatusCode())->toBe(200);
});

test('sin sesión responde 401 y no 403', function () {
    // Si alguien coloca el middleware sin 'auth' delante, tiene que decir «identifícate»,
    // no «no tienes permiso»: son problemas distintos y llevan a arreglos distintos.
    expect(pasaPor('DELETE', null, 'manage_catalog')->getStatusCode())->toBe(401);
});
