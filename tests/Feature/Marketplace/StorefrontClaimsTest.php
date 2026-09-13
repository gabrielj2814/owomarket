<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CentralCustomer;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CustomerReturnRequest;
use Src\Monetization\Infrastructure\Eloquent\Models\OrderDeliveryConfirmation;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant as ModelsTenant;
use Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper;
use Stancl\Tenancy\Events\TenantCreated;
use Stancl\Tenancy\Events\TenantDeleted;

/**
 * Reclamar desde el escaparate de una tienda (fase 2 de
 * `planes/ESTADO_DEL_PROYECTO.md`).
 *
 * **Lo que cierra:** desde la fase 1 el comprador de una tienda veía sus pedidos y confirmaba la
 * entrega, y ahí se le acababa el camino. Podía dar por recibido un producto roto y no tenía
 * dónde decirlo.
 *
 * Lo que se vigila aquí es lo mismo que en la fase 1 —la frontera de confianza— más las dos
 * reglas que esta fase cerró para los DOS caminos: entrega declarada y dentro de plazo.
 *
 * La reclamación acaba en la tabla CENTRAL aunque el pedido sea de tienda. Tiene que ser así:
 * el administrador, la reputación y el fondo de cobertura viven en la base central, y una
 * reclamación guardada en la del inquilino sería invisible para los tres.
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
        'customers' => '2025_10_28_144201_create_customers',
        'orders' => '2025_10_28_144320_create_orders',
        'order_items' => '2025_10_28_144403_create_order_items',
    ] as $tabla => $migracion) {
        if (! Schema::hasTable($tabla)) {
            (require base_path("database/migrations/tenant/{$migracion}.php"))->up();
        }
    }

    if (! Schema::hasColumn('customers', 'central_uuid')) {
        (require base_path('database/migrations/tenant/2026_08_19_000002_add_central_uuid_to_customers.php'))->up();
    }

    $tenantId = 't_'.bin2hex(random_bytes(4));
    $this->tenant = ModelsTenant::create([
        'id' => $tenantId,
        'name' => 'Tienda Reclamos',
        'slug' => $tenantId,
        'status' => 'active',
        'request' => 'approved',
    ]);
    $this->domain = "{$tenantId}.localhost";
    $this->tenant->domains()->create(['id' => (string) Str::uuid(), 'domain' => $this->domain]);

    // El comprador CENTRAL con su cédula: sin ella no se puede reclamar, y ésa es una regla
    // sobre la persona que no cambia por venir del escaparate.
    $this->comprador = CentralCustomer::create([
        'id' => (string) Str::uuid(),
        'name' => 'Ana Comprador',
        'email' => 'ana_'.Str::random(5).'@example.com',
        'document_id' => 'V-'.random_int(10000000, 29999999),
        'password' => 'secret',
    ]);
    $this->centralId = $this->comprador->id;

    $this->url = "http://{$this->domain}/api-tenant/storefront/returns";
});

/** Un pedido de esta tienda con un artículo, del comprador que se indique. */
function pedidoReclamable(?string $centralUuid, string $tenantId): array
{
    $customerId = (string) Str::uuid();
    DB::table('customers')->insert([
        'id' => $customerId,
        'central_uuid' => $centralUuid,
        'name' => 'Ana Comprador',
        'email' => 'tienda_'.Str::random(5).'@example.com',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $productId = (string) Str::uuid();
    DB::table('products')->insert([
        'id' => $productId,
        'name' => 'Auriculares',
        'slug' => 'auriculares-'.Str::random(4),
        'sku' => 'AUR-'.Str::random(4),
        'price' => 45.50,
        'quantity' => 10,
        'is_visible' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $orderId = (string) Str::uuid();
    DB::table('orders')->insert([
        'id' => $orderId,
        'customer_id' => $customerId,
        'order_number' => 'ORD-'.strtoupper(Str::random(5)),
        'status' => 'delivered',
        'subtotal' => 45.50,
        'total' => 45.50,
        'currency' => 'USD',
        'payment_method' => 'pago_movil',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('order_items')->insert([
        'id' => (string) Str::uuid(),
        'order_id' => $orderId,
        'product_id' => $productId,
        'product_name' => 'Auriculares',
        'sku' => 'AUR-1',
        'price' => 45.50,
        'quantity' => 1,
        'total' => 45.50,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return ['order_id' => $orderId, 'product_id' => $productId, 'tenant_id' => $tenantId];
}

function entregaDeTienda(string $orderId, string $tenantId, int $diasAtras): void
{
    OrderDeliveryConfirmation::create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $tenantId,
        'order_id' => $orderId,
        'declared_delivered_at' => now()->subDays($diasAtras),
    ]);
}

/** @return array{reason: string, description: string} */
function motivoYExplicacion(): array
{
    return [
        'reason' => 'Producto dañado o roto',
        'description' => 'Llegó con la diadema partida por la mitad.',
    ];
}

it('el comprador de una tienda abre una reclamación y queda en la tabla central', function () {
    $pedido = pedidoReclamable($this->centralId, $this->tenant->id);
    entregaDeTienda($pedido['order_id'], $this->tenant->id, 3);

    $this->withSession(['central_customer_id' => $this->centralId])
        ->postJson($this->url, array_merge(motivoYExplicacion(), [
            'order_id' => $pedido['order_id'],
            'product_id' => $pedido['product_id'],
        ]))
        ->assertOk()
        ->assertJson(['status' => 'success']);

    $reclamacion = CustomerReturnRequest::where('customer_id', $this->centralId)->first();

    expect($reclamacion)->not->toBeNull();
    expect($reclamacion->order_source)->toBe('storefront');
    expect($reclamacion->tenant_id)->toBe($this->tenant->id);
    /*
     * LA COLUMNA QUE CONECTA CON EL DINERO. La comisión de una venta de escaparate vive en
     * `platform_commissions.order_id` con el id del pedido de tienda; sin esta igualdad,
     * aprobar la reclamación no encontraría qué revertir.
     */
    expect($reclamacion->tenant_order_id)->toBe($pedido['order_id']);
    expect((float) $reclamacion->amount)->toBe(45.50);
});

it('no se puede reclamar el pedido de otro comprador', function () {
    // LA FRONTERA DE CONFIANZA. La identidad sale de la sesión del SSO, nunca del cuerpo. Un
    // fallo aquí deja abrir una reclamación --que mueve dinero-- sobre la compra de otro.
    $ajeno = pedidoReclamable((string) Str::uuid(), $this->tenant->id);
    entregaDeTienda($ajeno['order_id'], $this->tenant->id, 3);

    $this->withSession(['central_customer_id' => $this->centralId])
        ->postJson($this->url, array_merge(motivoYExplicacion(), [
            'order_id' => $ajeno['order_id'],
            'product_id' => $ajeno['product_id'],
        ]))
        ->assertStatus(404);

    expect(CustomerReturnRequest::count())->toBe(0);
});

it('un pedido de invitado no se puede reclamar', function () {
    // Sin `central_uuid` nadie puede demostrar que el pedido es suyo. Es el precio aceptado del
    // camino elegido en la fase 1, y el checkout lo advierte antes de pagar.
    $invitado = pedidoReclamable(null, $this->tenant->id);
    entregaDeTienda($invitado['order_id'], $this->tenant->id, 3);

    $this->withSession(['central_customer_id' => $this->centralId])
        ->postJson($this->url, array_merge(motivoYExplicacion(), [
            'order_id' => $invitado['order_id'],
            'product_id' => $invitado['product_id'],
        ]))
        ->assertStatus(404);
});

it('sin sesión responde 401 y no 404', function () {
    // Son cosas distintas: «no has entrado» frente a «ese pedido no es tuyo». La pantalla tiene
    // que poder invitar a entrar en vez de decir que el pedido no existe.
    $pedido = pedidoReclamable($this->centralId, $this->tenant->id);

    $this->postJson($this->url, array_merge(motivoYExplicacion(), [
        'order_id' => $pedido['order_id'],
        'product_id' => $pedido['product_id'],
    ]))->assertStatus(401);
});

it('no se puede reclamar antes de que la tienda declare la entrega', function () {
    // La regla que esta fase cerró. El filtro de pedidos vivía SOLO en la pantalla del portal:
    // contra la API se podía reclamar un pedido recién creado y sin pagar.
    $pedido = pedidoReclamable($this->centralId, $this->tenant->id);

    $this->withSession(['central_customer_id' => $this->centralId])
        ->postJson($this->url, array_merge(motivoYExplicacion(), [
            'order_id' => $pedido['order_id'],
            'product_id' => $pedido['product_id'],
        ]))
        ->assertStatus(422)
        ->assertJsonFragment(['message' => 'Todavía no puedes reclamar este pedido: la tienda no ha marcado la entrega.']);
});

it('fuera de la ventana ya no se puede reclamar', function () {
    $pedido = pedidoReclamable($this->centralId, $this->tenant->id);
    // Un día más allá del valor por defecto de `ClaimWindow`.
    entregaDeTienda($pedido['order_id'], $this->tenant->id, 61);

    $this->withSession(['central_customer_id' => $this->centralId])
        ->postJson($this->url, array_merge(motivoYExplicacion(), [
            'order_id' => $pedido['order_id'],
            'product_id' => $pedido['product_id'],
        ]))
        ->assertStatus(422);
});

it('mis-pedidos dice por artículo si se puede reclamar y qué se reclamó ya', function () {
    /*
     * `can_claim` lo decide el SERVIDOR con las mismas condiciones que aplica al recibir la
     * reclamación. Si la pantalla llegara a su propia conclusión, ofrecería un botón que el
     * backend rechaza --o escondería uno que sí funciona--.
     */
    $pedido = pedidoReclamable($this->centralId, $this->tenant->id);
    entregaDeTienda($pedido['order_id'], $this->tenant->id, 3);

    $antes = $this->withSession(['central_customer_id' => $this->centralId])
        ->getJson("http://{$this->domain}/api-tenant/storefront/my-orders")
        ->assertOk();

    expect($antes->json('data.0.items.0.can_claim'))->toBeTrue();
    expect($antes->json('data.0.items.0.claim'))->toBeNull();
    // La cédula viaja en `meta`: es condición de la PERSONA, no de la compra, y la pantalla la
    // necesita ANTES de abrir el formulario.
    expect($antes->json('meta.has_document_id'))->toBeTrue();

    $this->withSession(['central_customer_id' => $this->centralId])
        ->postJson($this->url, array_merge(motivoYExplicacion(), [
            'order_id' => $pedido['order_id'],
            'product_id' => $pedido['product_id'],
        ]))
        ->assertOk();

    $despues = $this->withSession(['central_customer_id' => $this->centralId])
        ->getJson("http://{$this->domain}/api-tenant/storefront/my-orders")
        ->assertOk();

    expect($despues->json('data.0.items.0.can_claim'))->toBeFalse();
    expect($despues->json('data.0.items.0.claim.status'))->toBe('requested');
});
