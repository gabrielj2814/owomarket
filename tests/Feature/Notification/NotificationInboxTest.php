<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CentralCustomer;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CustomerReturnRequest;
use Src\Notification\Application\Contracts\NotificationDispatcher;
use Src\Notification\Infrastructure\Eloquent\Models\Notification;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;
use Src\User\Infrastructure\Eloquent\Models\User;
use Stancl\Tenancy\Events\TenantCreated;
use Stancl\Tenancy\Events\TenantDeleted;

/**
 * El buzón (fase 1 de `planes/ESTADO_DEL_PROYECTO.md`).
 *
 * Lo que se vigila:
 *
 * 1. Que el aviso de reclamación llegue a **los dueños** de la tienda y a nadie más. Es el más
 *    urgente del sistema: sin él, el reloj resuelve a favor del comprador y el comerciante
 *    pierde la venta sin haber sabido que tenía que contestar.
 * 2. Que **el tipo guardado sea el alias**, no el nombre de una de las cuatro clases `User`.
 * 3. Que un buzón no deje ver ni marcar el de otro.
 */
beforeEach(function () {
    Event::fake([TenantCreated::class, TenantDeleted::class]);

    $this->tenant = Tenant::create([
        'id' => 'tienda-avisos',
        'name' => 'Tienda Avisos',
        'slug' => 'tienda-avisos',
        'status' => 'active',
        'request' => 'approved',
    ]);

    $this->dueno = personalDeTienda($this->tenant->id, 'owner');
    $this->empleado = personalDeTienda($this->tenant->id, 'staff');

    $this->avisos = app(NotificationDispatcher::class);
});

function personalDeTienda(string $tenantId, string $rol): User
{
    $user = User::create([
        'id' => (string) Str::uuid(),
        'name' => 'Persona '.$rol,
        'email' => $rol.'_'.Str::random(6).'@owomarket.com',
        'password' => bcrypt('password123'),
        'type' => 'tenant_user',
        'is_active' => true,
    ]);

    $user->tenants()->attach($tenantId, ['id' => (string) Str::uuid(), 'role' => $rol]);

    return $user;
}

function reclamacionDe(string $tenantId): CustomerReturnRequest
{
    return CustomerReturnRequest::create([
        'id' => (string) Str::uuid(),
        'order_id' => (string) Str::uuid(),
        'order_source' => 'central',
        'order_number' => 'ORD-AVISO-1',
        'tenant_order_id' => (string) Str::uuid(),
        'customer_id' => (string) Str::uuid(),
        'customer_email' => 'comprador@example.com',
        'product_id' => (string) Str::uuid(),
        'product_name' => 'Auriculares',
        'tenant_id' => $tenantId,
        'reason' => 'Producto dañado o roto',
        'description' => 'Llegaron rotos.',
        'status' => 'requested',
    ]);
}

it('avisa a los dueños de la tienda y no al resto del personal', function () {
    $reclamacion = reclamacionDe($this->tenant->id);

    $this->avisos->claimOpened($reclamacion->id);

    expect($this->dueno->notifications()->count())->toBe(1);
    // Un `staff` no responde por el dinero de la tienda. Avisar a los cuatro roles de todo
    // convertiría el buzón en ruido el primer día.
    expect($this->empleado->notifications()->count())->toBe(0);
});

it('el aviso lleva los días que quedan, sacados del mismo servicio que el reloj', function () {
    /*
     * Copiar el número en vez de leerlo de `ClaimResponseWindow` sería prometer un plazo que el
     * comando no respeta: la pantalla diría «te quedan 2 días» y esa noche la reclamación se
     * resolvería sola.
     */
    $reclamacion = reclamacionDe($this->tenant->id);

    $this->avisos->claimOpened($reclamacion->id);

    $datos = $this->dueno->notifications()->first()->data;

    expect($datos['type'])->toBe('claim.opened');
    expect($datos['days_left'])->toBe(app(\Src\CentralCustomer\Application\Service\ClaimResponseWindow::class)->days());
    expect($datos['order_number'])->toBe('ORD-AVISO-1');
    expect($datos['url'])->toContain($this->dueno->id);
});

it('guarda el alias del mapa y no el nombre de la clase', function () {
    /*
     * EL QUE EVITA QUE DESAPAREZCAN AVISOS. Hay CUATRO clases `User` sobre la tabla `users`;
     * sin mapa, la misma persona acumularía avisos bajo cuatro tipos y una consulta filtrada por
     * uno se dejaría los otros tres fuera, sin dar ningún error.
     */
    $this->avisos->claimOpened(reclamacionDe($this->tenant->id)->id);

    $fila = Notification::first();

    expect($fila->notifiable_type)->toBe('staff');
    expect($fila->notifiable_type)->not->toContain('\\');
});

it('una tienda sin dueños no revienta, solo no avisa', function () {
    $huerfana = Tenant::create([
        'id' => 'tienda-sin-dueno',
        'name' => 'Sin dueño',
        'slug' => 'sin-dueno',
        'status' => 'active',
        'request' => 'approved',
    ]);

    $this->avisos->claimOpened(reclamacionDe($huerfana->id)->id);

    expect(Notification::count())->toBe(0);
});

it('un fallo del despachador no sale hacia arriba', function () {
    /*
     * Es la regla del contrato, y no es prudencia genérica: `ResolveReturnRequestUseCase` corre
     * dentro de una transacción que revierte comisiones. Una excepción desde aquí desharía la
     * resolución de una reclamación y con ella el movimiento de dinero.
     */
    $this->avisos->claimOpened('no-existe-esta-reclamacion');

    // Si propagara, este test fallaria antes de llegar aqui: la asercion es la prueba.
    expect(Notification::count())->toBe(0);
});

it('el buzón solo devuelve lo de quien pregunta', function () {
    $otroDueno = personalDeTienda($this->tenant->id, 'owner');

    $this->avisos->claimOpened(reclamacionDe($this->tenant->id)->id);

    $mios = $this->actingAs($this->dueno)
        ->getJson('http://owomarket.local/api/notifications')
        ->assertOk()
        ->json('data');

    expect($mios['items'])->toHaveCount(1);
    expect($mios['unread'])->toBe(1);

    // El otro dueño recibió el suyo, que es una fila distinta: nunca ve la ajena.
    $suyos = $this->actingAs($otroDueno)
        ->getJson('http://owomarket.local/api/notifications')
        ->assertOk()
        ->json('data');

    expect($suyos['items'][0]['id'])->not->toBe($mios['items'][0]['id']);
});

it('no se puede marcar como leído el aviso de otro', function () {
    /*
     * No es solo molestar: un aviso leído desaparece del contador, así que esto le escondería a
     * un comerciante que tiene una reclamación con el reloj corriendo.
     */
    $otroDueno = personalDeTienda($this->tenant->id, 'owner');

    $this->avisos->claimOpened(reclamacionDe($this->tenant->id)->id);

    $ajeno = $otroDueno->notifications()->first();

    $this->actingAs($this->dueno)
        ->postJson('http://owomarket.local/api/notifications/read', ['id' => $ajeno->id])
        ->assertStatus(404);

    expect($otroDueno->unreadNotifications()->count())->toBe(1);
});

it('marcar todo deja el contador en cero', function () {
    $this->avisos->claimOpened(reclamacionDe($this->tenant->id)->id);
    $this->avisos->claimOpened(reclamacionDe($this->tenant->id)->id);

    $this->actingAs($this->dueno)
        ->postJson('http://owomarket.local/api/notifications/read')
        ->assertOk()
        ->assertJsonPath('data.marked', 2);

    expect($this->dueno->unreadNotifications()->count())->toBe(0);
});

it('sin sesión el buzón responde 401', function () {
    $this->getJson('http://owomarket.local/api/notifications')->assertStatus(401);
});

it('un comprador sin cuenta central enlazada no recibe aviso de entrega', function () {
    // Compra de invitado: `DeclareOrderDeliveredUseCase` no encuentra cuenta que enlazar, así
    // que el expediente nace sin `customer_id`. Es el precio aceptado del camino elegido en
    // los pedidos de escaparate, no una anomalía.
    \Src\Monetization\Infrastructure\Eloquent\Models\OrderDeliveryConfirmation::create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $this->tenant->id,
        'order_id' => $orderId = (string) Str::uuid(),
        'declared_delivered_at' => now(),
    ]);

    $this->avisos->deliveryDeclared($orderId);

    expect(Notification::count())->toBe(0);
});

it('el comprador enlazado sí recibe el aviso de entrega, con su alias propio', function () {
    $comprador = CentralCustomer::create([
        'id' => (string) Str::uuid(),
        'name' => 'Ana Compradora',
        'email' => 'ana_'.Str::random(5).'@example.com',
        'password' => 'secret',
    ]);

    \Src\Monetization\Infrastructure\Eloquent\Models\OrderDeliveryConfirmation::create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $this->tenant->id,
        'order_id' => $orderId = (string) Str::uuid(),
        'customer_id' => $comprador->id,
        'declared_delivered_at' => now(),
    ]);

    $this->avisos->deliveryDeclared($orderId);

    $fila = Notification::first();

    expect($fila->notifiable_type)->toBe('customer');
    expect($fila->data['type'])->toBe('delivery.declared');
    // Se le dice para qué sirve confirmar, no solo que confirme.
    expect($fila->data['body'])->toContain('se le paga');
});
