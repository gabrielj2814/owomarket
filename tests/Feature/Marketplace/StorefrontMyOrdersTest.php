<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Src\Monetization\Infrastructure\Eloquent\Models\OrderDeliveryConfirmation;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant as ModelsTenant;
use Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper;
use Stancl\Tenancy\Events\TenantCreated;
use Stancl\Tenancy\Events\TenantDeleted;

/**
 * Los pedidos del comprador en el escaparate (fase 1 de
 * `planes/por_hacer/PLAN_PEDIDOS_ESCAPARATE.md`).
 *
 * **Lo que arregla, y que no veía nadie:** el subsistema 3 ya estaba roto en el escaparate.
 * `DeclareOrderDeliveredUseCase` resuelve al comprador de una venta de tienda por
 * `customers.central_uuid`, y el checkout nunca rellenaba esa columna — así que toda venta de
 * escaparate nacía sin dueño en su expediente de entrega y **se liberaba siempre por
 * vencimiento del plazo**, porque nadie podía confirmar.
 *
 * Lo que se vigila aquí es sobre todo la frontera de confianza: que los pedidos se busquen por
 * la identidad de la SESIÓN y jamás por el correo, que se escribe a mano en el checkout.
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
        'name' => 'Tienda Escaparate',
        'slug' => $tenantId,
        'status' => 'active',
        'request' => 'approved',
    ]);
    $this->domain = "{$tenantId}.localhost";
    $this->tenant->domains()->create(['id' => (string) Str::uuid(), 'domain' => $this->domain]);

    $this->centralId = (string) Str::uuid();
});

/** Un comprador de la tienda, enlazado o no con una cuenta central. */
function compradorDeTienda(?string $centralUuid = null): string
{
    $id = (string) Str::uuid();

    DB::table('customers')->insert([
        'id' => $id,
        'central_uuid' => $centralUuid,
        'name' => 'Ana Comprador',
        'email' => 'ana_'.Str::random(5).'@example.com',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return $id;
}

function pedidoDeTienda(string $customerId, array $overrides = []): string
{
    $id = (string) Str::uuid();

    DB::table('orders')->insert(array_merge([
        'id' => $id,
        'customer_id' => $customerId,
        'order_number' => 'ORD-'.strtoupper(Str::random(5)),
        'status' => 'processing',
        'subtotal' => 100.0,
        'total' => 100.0,
        'currency' => 'USD',
        'payment_method' => 'pago_movil',
        'created_at' => now(),
        'updated_at' => now(),
    ], $overrides));

    return $id;
}

it('lista solo los pedidos del comprador que tiene la sesión', function () {
    // LA FRONTERA DE CONFIANZA. La búsqueda va por el identificador de la sesión, nunca por el
    // correo: el correo se escribe a mano en el checkout, así que aceptarlo dejaría que
    // cualquiera viera --y confirmara-- los pedidos de otro.
    $mio = compradorDeTienda($this->centralId);
    $ajeno = compradorDeTienda((string) Str::uuid());

    $pedidoMio = pedidoDeTienda($mio);
    $pedidoAjeno = pedidoDeTienda($ajeno);

    $pedidos = $this->withSession(['central_customer_id' => $this->centralId])
        ->getJson("http://{$this->domain}/api-tenant/storefront/my-orders")
        ->assertOk()
        ->json('data');

    $ids = collect($pedidos)->pluck('id')->all();

    expect($ids)->toContain($pedidoMio)
        ->and($ids)->not->toContain($pedidoAjeno);
});

it('sin sesión responde 401 y no una lista vacía', function () {
    // Son cosas distintas --«no has entrado» frente a «no has comprado nada»-- y la pantalla
    // tiene que poder decir cuál de las dos es.
    compradorDeTienda($this->centralId);

    $this->getJson("http://{$this->domain}/api-tenant/storefront/my-orders")
        ->assertStatus(401);
});

it('quien compró como invitado no ve nada, porque no hay enlace que lo demuestre', function () {
    // Precio aceptado del camino elegido: sin cuenta central no hay forma de probar que el
    // pedido es suyo. El checkout lo advierte antes de pagar.
    $invitado = compradorDeTienda(null);
    pedidoDeTienda($invitado);

    $this->withSession(['central_customer_id' => $this->centralId])
        ->getJson("http://{$this->domain}/api-tenant/storefront/my-orders")
        ->assertOk()
        ->assertJsonPath('data', []);
});

it('dice si al comprador le toca confirmar, y lo decide el servidor', function () {
    // `can_confirm` no lo calcula la pantalla: la misma regla la aplica
    // `ConfirmOrderDeliveryUseCase` al recibir la confirmación. Si divergieran, la pantalla
    // enseñaría un botón que el backend rechaza.
    $mio = compradorDeTienda($this->centralId);
    $pedido = pedidoDeTienda($mio);

    OrderDeliveryConfirmation::create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $this->tenant->id,
        'order_id' => $pedido,
        'customer_id' => $this->centralId,
        'declared_delivered_at' => now()->subDays(2),
    ]);

    $fila = collect($this->withSession(['central_customer_id' => $this->centralId])
        ->getJson("http://{$this->domain}/api-tenant/storefront/my-orders")
        ->assertOk()
        ->json('data'))
        ->firstWhere('id', $pedido);

    expect($fila['delivery']['can_confirm'])->toBeTrue();
});

it('un pedido sin entrega declarada todavía no se puede confirmar', function () {
    $mio = compradorDeTienda($this->centralId);
    $pedido = pedidoDeTienda($mio);

    OrderDeliveryConfirmation::create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $this->tenant->id,
        'order_id' => $pedido,
        'customer_id' => $this->centralId,
    ]);

    $fila = collect($this->withSession(['central_customer_id' => $this->centralId])
        ->getJson("http://{$this->domain}/api-tenant/storefront/my-orders")
        ->assertOk()
        ->json('data'))
        ->firstWhere('id', $pedido);

    expect($fila['delivery']['can_confirm'])->toBeFalse();
});

it('confirmar la entrega desde el escaparate libera la venta', function () {
    // EL TEST QUE IMPORTA. Es lo que libera el dinero del comerciante, y hasta ahora no existía
    // ninguna puerta para hacerlo desde una tienda: todas las ventas de escaparate se
    // liberaban por vencimiento del plazo.
    $mio = compradorDeTienda($this->centralId);
    $pedido = pedidoDeTienda($mio);

    $expediente = OrderDeliveryConfirmation::create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $this->tenant->id,
        'order_id' => $pedido,
        'customer_id' => $this->centralId,
        'declared_delivered_at' => now()->subDays(2),
    ]);

    $this->withSession(['central_customer_id' => $this->centralId])
        ->postJson("http://{$this->domain}/api-tenant/storefront/deliveries/{$pedido}/confirm")
        ->assertOk();

    $fresco = $expediente->fresh();

    expect($fresco->confirmed_at)->not->toBeNull()
        ->and($fresco->released_by)->toBe('customer')
        ->and($fresco->released_at)->not->toBeNull();
});

it('no se puede confirmar la entrega de un pedido ajeno', function () {
    $ajeno = compradorDeTienda((string) Str::uuid());
    $pedido = pedidoDeTienda($ajeno);

    $expediente = OrderDeliveryConfirmation::create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $this->tenant->id,
        'order_id' => $pedido,
        'customer_id' => (string) Str::uuid(),
        'declared_delivered_at' => now()->subDays(2),
    ]);

    $this->withSession(['central_customer_id' => $this->centralId])
        ->postJson("http://{$this->domain}/api-tenant/storefront/deliveries/{$pedido}/confirm")
        ->assertStatus(403);

    expect($expediente->fresh()->released_at)->toBeNull();
});

it('el expediente de entrega de otro responde 404, no 403', function () {
    // Confirmar que el pedido existe pero es de otro ya es filtrar información a quien no
    // debería tenerla.
    $ajeno = compradorDeTienda((string) Str::uuid());
    $pedido = pedidoDeTienda($ajeno);

    OrderDeliveryConfirmation::create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $this->tenant->id,
        'order_id' => $pedido,
        'customer_id' => (string) Str::uuid(),
        'declared_delivered_at' => now()->subDays(2),
    ]);

    $this->withSession(['central_customer_id' => $this->centralId])
        ->getJson("http://{$this->domain}/api-tenant/storefront/deliveries/{$pedido}")
        ->assertStatus(404);
});
