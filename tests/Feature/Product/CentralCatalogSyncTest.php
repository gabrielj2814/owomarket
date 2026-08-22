<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Src\Admin\Application\UseCase\ModerateCentralProductUseCase;
use Src\Marketplace\Application\Service\StockReserver;
use Src\Product\Domain\ValueObjects\ProductId;
use Src\Product\Infrastructure\Eloquent\Models\CentralProduct;
use Src\Product\Infrastructure\Eloquent\Models\Product as EloquentProduct;
use Src\Product\Infrastructure\Eloquent\Repositories\ProductRepository;
use Src\Product\Infrastructure\Jobs\SyncProductToCentralCatalogJob;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant as ModelsTenant;
use Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper;
use Stancl\Tenancy\Events\TenantCreated;
use Stancl\Tenancy\Events\TenantDeleted;

/**
 * Hallazgos E1 y E2: `SyncProductToCentralMarketplaceUseCase` existía, pero sólo lo
 * invocaba el botón de «publicar en el marketplace». Borrar, ocultar, editar o vender un
 * producto no llegaba nunca al catálogo central, que se quedaba congelado en el estado
 * del día de la publicación.
 *
 * Desde la Fase 0.4 el checkout central toma los precios de `central_products`, así que
 * un catálogo desincronizado dejó de ser cosmético: es dinero mal cobrado.
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
    ] as $table => $migration) {
        if (! Schema::hasTable($table)) {
            (require base_path("database/migrations/tenant/{$migration}.php"))->up();
        }
    }

    if (! Schema::hasColumn('products', 'is_published_central')) {
        (require base_path('database/migrations/tenant/2026_08_19_000006_add_marketplace_publication_to_products_table.php'))->up();
    }

    $tenantId = 't_sync_'.bin2hex(random_bytes(3));
    $this->tenant = ModelsTenant::create([
        'id' => $tenantId,
        'name' => 'Tienda Sync Test',
        'slug' => $tenantId,
        'status' => 'active',
        'request' => 'approved',
    ]);
    $this->tenant->domains()->create([
        'id' => (string) Str::uuid(),
        'domain' => "{$tenantId}.localhost",
    ]);

    tenancy()->initialize($this->tenant);

    $this->publishedProduct = function (array $overrides = []): EloquentProduct {
        return EloquentProduct::create(array_merge([
            'id' => (string) Str::uuid(),
            'name' => 'Camisa Blanca Clásica',
            'slug' => 'camisa-blanca-'.Str::random(4),
            'sku' => 'CAM-'.Str::random(4),
            'price' => 50.00,
            'quantity' => 10,
            'is_visible' => true,
            'track_quantity' => true,
            'is_published_central' => true,
        ], $overrides));
    };
});

afterEach(function () {
    if (tenancy()->initialized) {
        tenancy()->end();
    }
});

function centralRowFor(EloquentProduct $product): ?CentralProduct
{
    return CentralProduct::where('tenant_product_id', $product->id)->first();
}

test('creating a published product projects it onto the central catalogue', function () {
    $product = ($this->publishedProduct)();

    $central = centralRowFor($product);

    expect($central)->not->toBeNull()
        ->and($central->name)->toBe('Camisa Blanca Clásica')
        ->and((float) $central->price)->toBe(50.00)
        ->and($central->is_visible)->toBeTrue();
});

// E2: «el comerciante sube el precio de $50 a $80 y el marketplace sigue vendiendo a $50».
test('editing price and name reaches the central catalogue', function () {
    $product = ($this->publishedProduct)();

    $product->update(['price' => 80.00, 'name' => 'Camisa Blanca Premium']);

    $central = centralRowFor($product);

    expect((float) $central->price)->toBe(80.00)
        ->and($central->name)->toBe('Camisa Blanca Premium');
});

// E1: ocultar en la tienda tiene que retirar del marketplace.
test('hiding a product in the store withdraws it from the central catalogue', function () {
    $product = ($this->publishedProduct)();
    expect(centralRowFor($product)->is_visible)->toBeTrue();

    app(ProductRepository::class)->toggleVisibility(ProductId::fromString($product->id), false);

    expect(centralRowFor($product)->is_visible)->toBeFalse();
});

// E1: «el comerciante borra un producto descatalogado y sigue siendo comprable».
test('deleting a product withdraws it from the central catalogue', function () {
    $product = ($this->publishedProduct)();

    app(ProductRepository::class)->delete(ProductId::fromString($product->id));

    expect(centralRowFor($product)->is_visible)->toBeFalse();
});

// E2: «el decrement del checkout no pasaba por updateStock, así que el stock central
// tampoco bajaba con las ventas».
test('a sale lowers the stock in the central catalogue', function () {
    $product = ($this->publishedProduct)();
    expect(centralRowFor($product)->quantity)->toBe(10);

    app(StockReserver::class)->reserve($product->id, null, 3, $product->name);

    expect(centralRowFor($product)->quantity)->toBe(7);
});

test('restocking a cancelled order raises the stock in the central catalogue', function () {
    $product = ($this->publishedProduct)();

    app(StockReserver::class)->release($product->id, null, 5);

    expect(centralRowFor($product)->quantity)->toBe(15);
});

// Consecuencia de hacer la sincronización automática: sin la bandera de bloqueo, al
// comerciante le bastaría con editar el producto para revertir la decisión del moderador.
test('a moderator block survives the merchant editing the product', function () {
    $product = ($this->publishedProduct)();
    $central = centralRowFor($product);

    app(ModerateCentralProductUseCase::class)->execute($central->id, 'admin-uuid', [
        'is_visible' => false,
        'moderation_notes' => 'Fotografías engañosas.',
    ]);

    expect(centralRowFor($product)->is_visible)->toBeFalse();

    $product->update(['price' => 99.00]);

    $central = centralRowFor($product);
    expect($central->is_visible)->toBeFalse()
        ->and((float) $central->price)->toBe(99.00);
});

test('central-only metadata is not wiped by a sync from the store', function () {
    $product = ($this->publishedProduct)();
    $central = centralRowFor($product);

    app(ModerateCentralProductUseCase::class)->execute($central->id, 'admin-uuid', [
        'is_visible' => true,
        'moderation_notes' => 'Revisado y aprobado.',
        'commission_rate' => 3.5,
    ]);

    $product->update(['metadata' => ['origen' => 'importado']]);

    $metadata = centralRowFor($product)->metadata;

    expect($metadata['custom_commission_rate'])->toBe(3.5)
        ->and($metadata['moderation_history'])->toHaveCount(1)
        ->and($metadata['origen'])->toBe('importado');
});

// ---------------------------------------------------------------------------
// E3 — colisión de slugs entre tiendas
// ---------------------------------------------------------------------------

/**
 * En estos casos las filas centrales se crean directamente en vez de a través de dos
 * tiendas reales: la suite corre con `DatabaseTenancyBootstrapper` desactivado, así que
 * todas las «tiendas» comparten la misma base SQLite y el `unique` de `products.slug`
 * impediría el escenario. En producción cada tienda tiene su propia base y dos productos
 * con el mismo slug son lo normal — que es justo el origen del hallazgo.
 */
function centralProductFor(string $tenantId, string $slug, float $price): CentralProduct
{
    return CentralProduct::create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $tenantId,
        'tenant_product_id' => (string) Str::uuid(),
        'name' => 'Camisa Blanca',
        'slug' => $slug,
        'price' => $price,
        'quantity' => 5,
        'is_visible' => true,
    ]);
}

function secondTenant(): ModelsTenant
{
    $id = 't_sync_b_'.bin2hex(random_bytes(3));

    return ModelsTenant::create([
        'id' => $id,
        'name' => 'Tienda B',
        'slug' => $id,
        'status' => 'active',
        'request' => 'approved',
    ]);
}

// «La tienda A y la B publican camisa-blanca. Al abrir el producto de B desde el listado
// central se mostraba la ficha, el precio y la tienda de A.»
test('two stores sharing a slug resolve to their own product when addressed by id', function () {
    $tenantB = secondTenant();

    $centralA = centralProductFor($this->tenant->id, 'camisa-blanca', 50.00);
    $centralB = centralProductFor($tenantB->id, 'camisa-blanca', 90.00);

    $resolver = app(Src\Marketplace\Application\Service\CentralProductResolver::class);

    $resueltoA = $resolver->resolveVisible($centralA->id);
    $resueltoB = $resolver->resolveVisible($centralB->id);

    expect($resueltoA->tenant_id)->toBe($this->tenant->id)
        ->and((float) $resueltoA->price)->toBe(50.00)
        ->and($resueltoB->tenant_id)->toBe($tenantB->id)
        ->and((float) $resueltoB->price)->toBe(90.00);

    // Y por tenant_product_id, que también es un UUID inequívoco.
    expect($resolver->resolveVisible($centralB->tenant_product_id)->tenant_id)->toBe($tenantB->id);
});

test('an ambiguous slug resolves to the same product every time', function () {
    $tenantB = secondTenant();

    $candidatos = [
        centralProductFor($this->tenant->id, 'camisa-blanca', 50.00)->id,
        centralProductFor($tenantB->id, 'camisa-blanca', 90.00)->id,
    ];

    $resolver = app(Src\Marketplace\Application\Service\CentralProductResolver::class);

    // El slug es ambiguo por naturaleza en una URL global sin tienda, así que no se puede
    // garantizar CUÁL de los dos gana. Lo que sí se garantiza es que la misma URL lleve
    // siempre a la misma ficha, en vez de depender del orden que devuelva la base de datos.
    $primeraLectura = $resolver->resolveVisible('camisa-blanca')->id;

    expect($primeraLectura)->toBeIn($candidatos)
        ->and($resolver->resolveVisible('camisa-blanca')->id)->toBe($primeraLectura)
        ->and($resolver->resolveVisible('camisa-blanca')->id)->toBe($primeraLectura);
});

test('the central catalogue rejects two products with the same slug within one store', function () {
    centralProductFor($this->tenant->id, 'camisa-blanca', 50.00);

    expect(fn () => centralProductFor($this->tenant->id, 'camisa-blanca', 70.00))
        ->toThrow(Illuminate\Database\UniqueConstraintViolationException::class);
});

/*
|--------------------------------------------------------------------------
| Hallazgo N25 — la sincronización se encola
|--------------------------------------------------------------------------
|
| Escribía en la base central dentro de la misma petición que había tocado el producto,
| incluida la transacción del checkout. Si el marketplace no respondía, la fila quedaba
| desincronizada y sólo quedaba una línea de log que nada reintentaba. Y desde la Fase
| 0.4 el checkout central toma los precios de `central_products`: una fila desincronizada
| es dinero mal cobrado.
*/

test('guardar un producto encola la sincronización en vez de hacerla en la petición (N25)', function () {
    Queue::fake();

    $product = ($this->publishedProduct)();

    Queue::assertPushed(SyncProductToCentralCatalogJob::class);

    // La escritura de la tienda no espera al marketplace.
    expect(EloquentProduct::find($product->id))->not->toBeNull();
});

test('borrar un producto encola su retirada del catálogo (N25)', function () {
    $product = ($this->publishedProduct)();

    Queue::fake();
    $product->delete();

    Queue::assertPushed(SyncProductToCentralCatalogJob::class);
});

test('un fallo al encolar no tumba la escritura de la tienda (N25)', function () {
    // Abortar una venta porque el marketplace no responde sería peor que la
    // desincronización que causa, así que encolar tampoco puede propagar su fallo.
    Queue::shouldReceive('connection')->andThrow(new RuntimeException('Cola caída'));

    $product = ($this->publishedProduct)();

    expect(EloquentProduct::find($product->id))->not->toBeNull();
});
