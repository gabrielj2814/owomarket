<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant as ModelsTenant;
use Src\Tenant\Infrastructure\Eloquent\Models\User;
use Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper;
use Stancl\Tenancy\Events\TenantCreated;
use Stancl\Tenancy\Events\TenantDeleted;

/*
|--------------------------------------------------------------------------
| Hallazgo PR1 - borrado arbitrario de ficheros del disco publico
|--------------------------------------------------------------------------
|
| DeleteProductImageDELETEController tomaba `image_path` directamente del request, sin
| validarlo, y LaravelProductMediaStorageService hacia Storage::disk('public')->delete()
| con esa ruta tal cual. No comprobaba que el fichero fuese de ese producto, de esa tienda,
| ni que estuviera en el directorio de imagenes de producto.
|
| CORRECCION IMPORTANTE sobre el alcance. La primera version de este hallazgo decia que se
| podian borrar ficheros de OTRAS tiendas y del hub central -avatares de administrador, PDFs
| de factura-. Es FALSO, y se comprobo ejecutando:
|
|     raiz SIN tenancy: /var/www/storage/app/public
|     raiz CON tenancy: /var/www/storage/tenant{id}/app/public/
|
| `FilesystemTenancyBootstrapper` sufija el disco `public` por inquilino (ver
| config/tenancy.php, seccion filesystem), asi que durante una peticion de tienda el borrado
| NO puede salir del almacenamiento de esa tienda. Lo del hub central se escribe sin tenancy,
| en la raiz sin sufijar, y es inalcanzable desde aqui.
|
| Lo que SI queda: dentro de su propia tienda, cualquiera con `manage_catalog` -el permiso
| mas basico del catalogo, el de cualquier empleado que suba productos- puede borrar
| cualquier fichero pasando su ruta. Y ahi viven las facturas y los adjuntos de soporte DE
| ESA TIENDA, que son registros, no adorno.
|
| Por eso estos tests trabajan dentro de una sola tienda: es donde esta el problema real.
*/
beforeEach(function () {
    Event::fake([TenantCreated::class, TenantDeleted::class]);

    config([
        'tenancy.bootstrappers' => array_values(array_filter(
            config('tenancy.bootstrappers', []),
            fn ($b) => $b !== DatabaseTenancyBootstrapper::class
        )),
    ]);

    Storage::fake('public');

    $tenantId = 'pr1_'.bin2hex(random_bytes(3));
    $this->tenant = ModelsTenant::create([
        'id' => $tenantId,
        'name' => 'Tienda PR1',
        'slug' => $tenantId,
        'status' => 'active',
        'request' => 'approved',
    ]);

    $this->domain = "{$tenantId}.localhost";
    $this->tenant->domains()->create([
        'id' => (string) Str::uuid(),
        'domain' => $this->domain,
    ]);

    $this->tenantUser = User::create([
        'id' => (string) Str::uuid(),
        'name' => 'Empleado de catalogo',
        'email' => 'catalogo_'.bin2hex(random_bytes(4)).'@example.com',
        'password' => bcrypt('OwO_12345678'),
        'type' => 'tenant_owner',
    ]);

    $this->actingAs($this->tenantUser);
});

/*
 * Los casos NEGATIVOS se comprueban contra el servicio y no por HTTP, y conviene decir por
 * qué: `Storage::fake()` y el sufijado de disco de la tenancy no conviven bien en un test de
 * peticion, y una asercion sobre el disco despues de un `deleteJson` mide una raiz distinta
 * de la que uso el request. Se probo, dio falsos negativos, y un test que falla por el
 * andamiaje es peor que no tenerlo.
 *
 * El caso POSITIVO si va por HTTP —abajo—, que es donde importa comprobar que la cadena
 * entera sigue funcionando: controlador, caso de uso y servicio.
 */
test('el borrado de imagenes no alcanza las facturas ni los adjuntos de la tienda (PR1)', function () {
    $servicio = app(Src\Product\Application\Contracts\ProductMediaStorageInterface::class);

    // Ficheros de la MISMA tienda que no son imagenes de producto. Son registros: la prueba
    // documental de una transaccion y de una reclamacion.
    Storage::disk('public')->put('invoices/FAC-2026-0001.pdf', 'contenido');
    Storage::disk('public')->put('support/adjunto-reclamacion.png', 'contenido');

    $servicio->deleteImage('invoices/FAC-2026-0001.pdf', $this->tenant->id);
    $servicio->deleteImage('support/adjunto-reclamacion.png', $this->tenant->id);

    expect(Storage::disk('public')->exists('invoices/FAC-2026-0001.pdf'))->toBeTrue();
    expect(Storage::disk('public')->exists('support/adjunto-reclamacion.png'))->toBeTrue();
});

test('sin inquilino no se borra nada (PR1)', function () {
    $servicio = app(Src\Product\Application\Contracts\ProductMediaStorageInterface::class);

    Storage::disk('public')->put("tenants/{$this->tenant->id}/products/huerfana.jpg", 'contenido');

    // Fuera de una tienda no hay a quien atribuir el fichero. Se niega en vez de dejar pasar:
    // negar de mas es recuperable, borrar de mas no.
    $servicio->deleteImage("tenants/{$this->tenant->id}/products/huerfana.jpg", null);

    expect(Storage::disk('public')->exists("tenants/{$this->tenant->id}/products/huerfana.jpg"))->toBeTrue();
});

test('una ruta con .. se rechaza sin tocar el disco (PR1)', function () {
    $servicio = app(Src\Product\Application\Contracts\ProductMediaStorageInterface::class);

    Storage::disk('public')->put("tenants/{$this->tenant->id}/products/legitima.jpg", 'contenido');

    // Flysystem ya impide subir por el arbol, pero un `..` en la ruta significa que quien la
    // manda esta intentando algo: se rechaza antes, no se confia en la capa de abajo.
    $servicio->deleteImage("tenants/{$this->tenant->id}/products/../../../.env", $this->tenant->id);

    expect(Storage::disk('public')->exists("tenants/{$this->tenant->id}/products/legitima.jpg"))->toBeTrue();
});

test('si se puede borrar la imagen propia, por ruta y por URL (PR1)', function () {
    // Cerrar la puerta no puede llevarse por delante el caso legitimo: el formulario borra
    // una imagen recien subida ANTES de guardar el producto, asi que no hay ninguna fila de
    // `product_images` que la reclame todavia.
    $propia = "tenants/{$this->tenant->id}/products/propia.jpg";
    Storage::disk('public')->put($propia, 'contenido');

    $this->deleteJson("http://{$this->domain}/api-tenant/product/media/delete", [
        'image_path' => $propia,
    ])->assertStatus(200);

    expect(Storage::disk('public')->exists($propia))->toBeFalse();

    // El formulario manda la URL completa, no la ruta: el servicio la recorta.
    $porUrl = "tenants/{$this->tenant->id}/products/por-url.jpg";
    Storage::disk('public')->put($porUrl, 'contenido');

    $this->deleteJson("http://{$this->domain}/api-tenant/product/media/delete", [
        'image_path' => "http://{$this->domain}/storage/{$porUrl}",
    ])->assertStatus(200);

    expect(Storage::disk('public')->exists($porUrl))->toBeFalse();
});
