<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Src\Product\Infrastructure\Eloquent\Models\Product;
use Src\Product\Infrastructure\Eloquent\Models\ProductVariant;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant as ModelsTenant;
use Src\Tenant\Infrastructure\Eloquent\Models\User;
use Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper;
use Stancl\Tenancy\Events\TenantCreated;
use Stancl\Tenancy\Events\TenantDeleted;

/*
|--------------------------------------------------------------------------
| Hallazgo PR2 - reponer stock de un producto con variantes no hacia nada
|--------------------------------------------------------------------------
|
| `ProductRepository::updateStock()` escribia SOLO en `products.quantity` y nunca tocaba
| `product_variants`. En un producto con variantes ese campo no lo mantiene ni lo lee nadie
| —lo dice el propio codigo en el hallazgo N36: «StockReserver solo descuenta de la
| variante»— y la ficha de producto muestra el de la variante cuando la hay.
|
| Asi que el comerciante corregia el stock desde su lista, veia que se guardaba, y ni lo que
| se vende ni lo que se muestra cambiaba. Un producto agotado seguia agotado despues de
| reponerlo, sin error y sin pista de por que.
*/
beforeEach(function () {
    Event::fake([TenantCreated::class, TenantDeleted::class]);

    config([
        'tenancy.bootstrappers' => array_values(array_filter(
            config('tenancy.bootstrappers', []),
            fn ($b) => $b !== DatabaseTenancyBootstrapper::class
        )),
    ]);

    foreach ([
        'categories' => '2025_10_28_142911_create_categories',
        'brands' => '2025_10_28_143000_create_brands',
        'products' => '2025_10_28_143038_create_products',
        'product_variants' => '2025_10_28_143954_create_product_variants',
        // `findById` carga con eager loading las imagenes y, por cada variante, sus
        // valores de atributo a traves de un pivote. Sin estas tablas el endpoint revienta
        // antes de llegar a la logica de stock — y el 500 no dice cual falta.
        'product_images' => '2025_10_28_143251_create_product_images',
        'product_attributes' => '2025_10_28_143325_create_product_attributes',
        'product_attribute_values' => '2025_10_28_143921_create_product_attribute_values',
        'product_variant_attribute_values' => '2025_10_28_144041_create_product_variant_attribute_values',
    ] as $tabla => $migracion) {
        if (! Schema::hasTable($tabla)) {
            (require base_path("database/migrations/tenant/{$migracion}.php"))->up();
        }
    }

    $tenantId = 'pr2_'.bin2hex(random_bytes(3));
    $this->tenant = ModelsTenant::create([
        'id' => $tenantId,
        'name' => 'Tienda PR2',
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

    $this->producto = Product::create([
        'id' => (string) Str::uuid(),
        'name' => 'Camiseta',
        'slug' => 'camiseta-'.bin2hex(random_bytes(3)),
        'sku' => 'CAM-'.bin2hex(random_bytes(3)),
        'price' => 20.00,
        'quantity' => 0,
        'is_visible' => true,
    ]);
});

test('reponer stock de un producto SIN variantes sigue funcionando (PR2)', function () {
    $this->patchJson("http://{$this->domain}/api-tenant/product/{$this->producto->id}/stock", [
        'quantity' => 25,
    ])->assertStatus(200);

    expect($this->producto->fresh()->quantity)->toBe(25);
});

test('reponer stock de un producto CON variantes sin decir cual se rechaza (PR2)', function () {
    ProductVariant::create([
        'id' => (string) Str::uuid(),
        'product_id' => $this->producto->id,
        'sku' => 'CAM-M',
        'price' => 20.00,
        'quantity' => 0,
        'attributes' => ['talla' => 'M'],
    ]);

    // Antes esto devolvia 200 y escribia en `products.quantity`, un campo que nadie lee en
    // un producto con variantes. El comerciante se iba creyendo que habia repuesto.
    $this->patchJson("http://{$this->domain}/api-tenant/product/{$this->producto->id}/stock", [
        'quantity' => 25,
    ])->assertStatus(422);

    expect($this->producto->fresh()->quantity)->toBe(0);
});

test('reponer stock de una variante concreta si funciona (PR2)', function () {
    $variante = ProductVariant::create([
        'id' => (string) Str::uuid(),
        'product_id' => $this->producto->id,
        'sku' => 'CAM-L',
        'price' => 20.00,
        'quantity' => 0,
        'attributes' => ['talla' => 'M'],
    ]);

    $this->patchJson("http://{$this->domain}/api-tenant/product/{$this->producto->id}/stock", [
        'quantity' => 25,
        'variant_id' => $variante->id,
    ])->assertStatus(200);

    // Se escribe donde se vende y donde se muestra.
    expect($variante->fresh()->quantity)->toBe(25);
});

test('no se puede reponer una variante de OTRO producto (PR2)', function () {
    $otro = Product::create([
        'id' => (string) Str::uuid(),
        'name' => 'Pantalon',
        'slug' => 'pantalon-'.bin2hex(random_bytes(3)),
        'sku' => 'PAN-'.bin2hex(random_bytes(3)),
        'price' => 30.00,
        'quantity' => 0,
        'is_visible' => true,
    ]);

    $varianteAjena = ProductVariant::create([
        'id' => (string) Str::uuid(),
        'product_id' => $otro->id,
        'sku' => 'PAN-42',
        'price' => 30.00,
        'quantity' => 5,
        'attributes' => ['talla' => '42'],
    ]);

    $this->patchJson("http://{$this->domain}/api-tenant/product/{$this->producto->id}/stock", [
        'quantity' => 999,
        'variant_id' => $varianteAjena->id,
    ])->assertStatus(422);

    expect($varianteAjena->fresh()->quantity)->toBe(5);
});
