<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Src\Product\Infrastructure\Eloquent\Models\Product as EloquentProduct;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant as ModelsTenant;
use Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper;
use Stancl\Tenancy\Events\TenantCreated;
use Stancl\Tenancy\Events\TenantDeleted;

/*
|--------------------------------------------------------------------------
| Hallazgo N19 — control de rol dentro de la tienda
|--------------------------------------------------------------------------
|
| `/api-tenant/*` llevaba 'web' + tenancy + 'auth' y nada mas. Cualquiera con sesion en
| la tienda, incluido un `staff` recien contratado, podia borrar el catalogo entero o
| anular facturas exactamente igual que el propietario.
*/

beforeEach(function () {
    Event::fake([TenantCreated::class, TenantDeleted::class]);

    config([
        'tenancy.bootstrappers' => array_values(array_filter(
            config('tenancy.bootstrappers', []),
            fn ($bootstrapper) => $bootstrapper !== DatabaseTenancyBootstrapper::class
        )),
    ]);

    foreach ([
        'categories' => '2025_10_28_142911_create_categories',
        'brands' => '2025_10_28_143000_create_brands',
        'products' => '2025_10_28_143038_create_products',
        'product_images' => '2025_10_28_143251_create_product_images',
        'product_variants' => '2025_10_28_143954_create_product_variants',
        'permissions' => '2026_08_19_000010_create_permission_tables',
    ] as $table => $migration) {
        if (! Schema::hasTable($table)) {
            (require base_path("database/migrations/tenant/{$migration}.php"))->up();
        }
    }

    if (! Schema::hasColumn('products', 'is_published_central')) {
        (require base_path('database/migrations/tenant/2026_08_19_000006_add_marketplace_publication_to_products_table.php'))->up();
    }

    $tenantId = 't_roles_'.bin2hex(random_bytes(3));
    $this->tenant = ModelsTenant::create([
        'id' => $tenantId,
        'name' => 'Tienda Roles',
        'slug' => $tenantId,
        'status' => 'active',
        'request' => 'approved',
    ]);
    $this->domain = "{$tenantId}.localhost";
    $this->tenant->domains()->create([
        'id' => (string) Str::uuid(),
        'domain' => $this->domain,
    ]);

    tenancy()->initialize($this->tenant);

    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Permission::findOrCreate('manage_catalog', 'web');

    $this->producto = EloquentProduct::create([
        'id' => (string) Str::uuid(),
        'name' => 'Producto Bajo Permisos',
        'slug' => 'producto-permisos-'.Str::random(4),
        'sku' => 'PERM-'.Str::random(4),
        'price' => 30.00,
        'quantity' => 5,
        'is_visible' => true,
    ]);
});

afterEach(function () {
    if (tenancy()->initialized) {
        tenancy()->end();
    }
});

/*
| Nota sobre el tipo de usuario: la suite comparte la tabla `users` central, cuyo enum
| sólo admite super_admin, tenant_owner y customer — los tipos `owner`/`staff` viven en la
| tabla `users` de cada base de inquilino, que aquí no se monta porque el
| DatabaseTenancyBootstrapper está desactivado. Lo que el middleware comprueba de verdad
| es «¿es uno de los tipos de propietario?», y `customer` recorre exactamente esa rama.
| El literal 'staff' se cubre en el test unitario del middleware.
*/
test('un usuario sin permisos no puede borrar el catálogo (N19)', function () {
    actingAsTenantOwner('customer');

    $this->deleteJson("http://{$this->domain}/api-tenant/product/{$this->producto->id}")
        ->assertStatus(403)
        ->assertJsonPath('status', 'error');

    // Y no ha borrado nada.
    expect(EloquentProduct::find($this->producto->id))->not->toBeNull();
});

test('un usuario sin permisos sí puede consultar el catálogo (N19)', function () {
    // El control es sobre lo que ESCRIBE. Un staff necesita leer para trabajar; si las
    // lecturas también se cerraran, el rol no serviría para nada.
    actingAsTenantOwner('customer');

    $respuesta = $this->getJson("http://{$this->domain}/api-tenant/product/{$this->producto->id}");

    expect($respuesta->status())->not->toBe(403);
});

test('con el permiso concedido sí puede escribir (N19)', function () {
    $staff = actingAsTenantOwner('customer');
    $staff->givePermissionTo('manage_catalog');

    $this->deleteJson("http://{$this->domain}/api-tenant/product/{$this->producto->id}")
        ->assertStatus(200);
});

test('el propietario pasa sin necesidad de filas de permisos (N19)', function () {
    // A propósito: hacerle depender del aprovisionamiento abriría la puerta a que un
    // fallo lo dejara fuera de su propio negocio.
    actingAsTenantOwner('tenant_owner');

    $this->deleteJson("http://{$this->domain}/api-tenant/product/{$this->producto->id}")
        ->assertStatus(200);
});
