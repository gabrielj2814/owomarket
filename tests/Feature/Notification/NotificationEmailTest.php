<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Str;
use Src\CentralCustomer\Application\UseCases\SendCentralCustomerPasswordResetPinUseCase;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CentralCustomer;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CustomerReturnRequest;
use Src\CentralCustomer\Infrastructure\Notifications\PasswordResetPinNotification;
use Src\Notification\Application\Contracts\NotificationDispatcher;
use Src\Notification\Application\Service\EmailDelivery;
use Src\Notification\Infrastructure\Laravel\InboxNotification;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;
use Src\User\Infrastructure\Eloquent\Models\User;
use Stancl\Tenancy\Events\TenantCreated;
use Stancl\Tenancy\Events\TenantDeleted;

/**
 * El canal de correo (fase 3 de `planes/futuros/PLAN_NOTIFICACIONES.md`).
 *
 * Lo que se vigila es **el reparto**, porque los dos errores posibles duelen en direcciones
 * opuestas:
 *
 * - Mandar de más llena de correo a quien no lo pidió, y entonces deja de leer también los que
 *   importan.
 * - Mandar de menos deja a un comerciante sin enterarse de una reclamación con el reloj
 *   corriendo.
 *
 * Por eso lo crítico no se puede apagar y el resto llega apagado.
 */
beforeEach(function () {
    Event::fake([TenantCreated::class, TenantDeleted::class]);

    $this->tenant = Tenant::create([
        'id' => 'tienda-correo',
        'name' => 'Tienda Correo',
        'slug' => 'tienda-correo',
        'status' => 'active',
        'request' => 'approved',
    ]);

    $this->dueno = User::create([
        'id' => (string) Str::uuid(),
        'name' => 'Dueña',
        'email' => 'duena_'.Str::random(6).'@owomarket.com',
        'password' => bcrypt('x'),
        'is_active' => true,
    ]);
    $this->dueno->tenants()->attach($this->tenant->id, ['id' => (string) Str::uuid(), 'role' => 'owner']);

    $this->avisos = app(NotificationDispatcher::class);
    $this->correo = app(EmailDelivery::class);
});

function reclamacionParaCorreo(string $tenantId): CustomerReturnRequest
{
    return CustomerReturnRequest::create([
        'id' => (string) Str::uuid(),
        'order_id' => (string) Str::uuid(),
        'order_source' => 'central',
        'order_number' => 'ORD-MAIL-1',
        'tenant_order_id' => (string) Str::uuid(),
        'customer_id' => (string) Str::uuid(),
        'customer_email' => 'ana@example.com',
        'product_id' => (string) Str::uuid(),
        'product_name' => 'Auriculares',
        'tenant_id' => $tenantId,
        'reason' => 'Producto dañado o roto',
        'description' => 'Llegaron rotos.',
        'status' => 'requested',
    ]);
}

it('un aviso crítico sale por correo sin que nadie lo active', function () {
    /*
     * EL REPARTO QUE IMPORTA. La reclamación abierta tiene un reloj: a los cinco días se
     * resuelve a favor del comprador. Esperar a que el comerciante active una casilla para
     * avisarle de eso sería ponerle la alarma de incendios en modo silencio por defecto.
     */
    NotificationFacade::fake();

    $this->avisos->claimOpened(reclamacionParaCorreo($this->tenant->id)->id);

    NotificationFacade::assertSentTo(
        $this->dueno,
        InboxNotification::class,
        fn ($n, $canales) => in_array('mail', $canales, true) && in_array('database', $canales, true),
    );
});

function retiroParaCorreo(string $tenantId)
{
    return \Src\Monetization\Infrastructure\Eloquent\Models\CommissionSettlement::create([
        'id' => (string) Str::uuid(),
        'settlement_number' => 'SET-'.strtoupper(Str::random(5)),
        'tenant_id' => $tenantId,
        'type' => 'payout',
        'gross_sales_amount' => 100.0,
        'commission_amount' => 8.0,
        'net_amount' => 92.0,
        'currency' => 'USD',
        'status' => 'settled',
    ]);
}

it('un aviso no crítico llega solo al buzón mientras no se active el correo', function () {
    NotificationFacade::fake();

    // `payout.resolved` no está en la lista de críticos: importa, pero no tiene reloj.
    $this->avisos->payoutResolved(retiroParaCorreo($this->tenant->id)->id);

    NotificationFacade::assertSentTo(
        $this->dueno,
        InboxNotification::class,
        fn ($n, $canales) => $canales === ['database'],
    );
});

it('activando el correo, los avisos normales también salen por correo', function () {
    $this->correo->setEmailEnabled($this->dueno, true);

    NotificationFacade::fake();

    $this->avisos->payoutResolved(retiroParaCorreo($this->tenant->id)->id);

    NotificationFacade::assertSentTo(
        $this->dueno,
        InboxNotification::class,
        fn ($n, $canales) => in_array('mail', $canales, true),
    );
});

it('desactivar el correo NO apaga los avisos críticos', function () {
    /*
     * Lo que la pantalla promete tiene que ser verdad. La campana dice «los avisos urgentes te
     * llegan siempre»: si desactivar los apagara, alguien perdería una reclamación creyendo que
     * solo había silenciado ruido.
     */
    $this->correo->setEmailEnabled($this->dueno, false);

    NotificationFacade::fake();

    $this->avisos->claimOpened(reclamacionParaCorreo($this->tenant->id)->id);

    NotificationFacade::assertSentTo(
        $this->dueno,
        InboxNotification::class,
        fn ($n, $canales) => in_array('mail', $canales, true),
    );
});

it('la preferencia se guarda con el alias del mapa, no con el nombre de la clase', function () {
    // Con cuatro clases `User` sobre la tabla `users`, guardar el nombre de la clase haría que
    // la preferencia de una persona no se encontrara al cargarla con otra.
    $this->correo->setEmailEnabled($this->dueno, true);

    $fila = \Src\Notification\Infrastructure\Eloquent\Models\NotificationPreference::first();

    expect($fila->notifiable_type)->toBe('staff');
    expect($this->correo->emailEnabled($this->dueno))->toBeTrue();
});

it('el interruptor viaja con el buzón', function () {
    $this->correo->setEmailEnabled($this->dueno, true);

    $this->actingAs($this->dueno)
        ->getJson('http://owomarket.local/api/notifications')
        ->assertOk()
        ->assertJsonPath('data.email_enabled', true);
});

it('se puede apagar y encender desde la campana', function () {
    $this->actingAs($this->dueno)
        ->postJson('http://owomarket.local/api/notifications/email-preference', ['email_enabled' => true])
        ->assertOk()
        ->assertJsonPath('data.email_enabled', true);

    expect($this->correo->emailEnabled($this->dueno))->toBeTrue();

    $respuesta = $this->actingAs($this->dueno)
        ->postJson('http://owomarket.local/api/notifications/email-preference', ['email_enabled' => false])
        ->assertOk();

    // El mensaje tiene que recordar que lo urgente sigue llegando: si no, alguien cree que lo
    // apagó todo.
    expect($respuesta->json('message'))->toContain('urgentes seguirán');
    expect($this->correo->emailEnabled($this->dueno))->toBeFalse();
});

it('nadie puede cambiarle la preferencia a otro', function () {
    $this->postJson('http://owomarket.local/api/notifications/email-preference', ['email_enabled' => true])
        ->assertStatus(401);
});

it('el código de recuperación de contraseña ya se envía', function () {
    /*
     * Era un callejón sin salida: el PIN se generaba, se guardaba con 15 minutos de caducidad y
     * **no se mandaba a ningún sitio**. El controlador lo devuelve en la respuesta solo en
     * `local` y `testing`, así que en producción nadie podía recuperar su contraseña.
     */
    $comprador = CentralCustomer::create([
        'id' => (string) Str::uuid(),
        'name' => 'Ana',
        // En minusculas a proposito: `RegisterCentralCustomerUseCase` guarda siempre asi, y
        // el caso de uso de recuperacion busca normalizado. Un fixture con mayusculas probaria
        // un caso que no existe en la base real.
        'email' => 'ana_'.strtolower(Str::random(5)).'@example.com',
        'password' => 'secret',
    ]);

    NotificationFacade::fake();

    app(SendCentralCustomerPasswordResetPinUseCase::class)->execute($comprador->email);

    NotificationFacade::assertSentOnDemand(PasswordResetPinNotification::class);
});

it('un correo que no tiene cuenta no dispara ningún envío', function () {
    // La salida silenciosa del hallazgo A3: misma respuesta, mismo mensaje, y **ningún correo**.
    // Mandar uno aquí delataría qué direcciones tienen cuenta.
    NotificationFacade::fake();

    $resultado = app(SendCentralCustomerPasswordResetPinUseCase::class)->execute('nadie_'.strtolower(Str::random(6)).'@example.com');

    NotificationFacade::assertNothingSent();
    expect($resultado['message'])->toContain('Si ese correo tiene una cuenta');
});
