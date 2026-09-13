<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Src\CentralCustomer\Application\Service\ClaimResponseWindow;
use Src\CentralCustomer\Application\UseCases\RemindExpiringClaimsUseCase;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CustomerReturnRequest;
use Src\Monetization\Infrastructure\Eloquent\Models\PlatformCommission;
use Src\Notification\Application\Contracts\NotificationDispatcher;
use Src\Notification\Application\Service\EmailDelivery;
use Src\Notification\Infrastructure\Eloquent\Models\Notification;
use Src\Payment\Infrastructure\Eloquent\Models\CentralSetting;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;
use Src\User\Infrastructure\Eloquent\Models\User;
use Stancl\Tenancy\Events\TenantCreated;
use Stancl\Tenancy\Events\TenantDeleted;

/**
 * Lo periódico (fase 4 de `planes/futuros/PLAN_NOTIFICACIONES.md`).
 *
 * Los dos avisos de esta fase los dispara un comando que corre **todos los días** sobre un estado
 * que no cambia: el mes sigue pasado del techo mañana, y la reclamación sigue a punto de vencer
 * hasta que alguien conteste.
 *
 * Por eso **lo que de verdad se prueba aquí es el freno**. Sin él los dos avisos saldrían cada
 * madrugada, y un aviso repetido no avisa el doble: avisa menos, porque enseña a ignorarlo.
 */
beforeEach(function () {
    Event::fake([TenantCreated::class, TenantDeleted::class]);
    Cache::flush();

    $this->tenant = Tenant::create([
        'id' => 'tienda-periodica',
        'name' => 'Tienda Periódica',
        'slug' => 'tienda-periodica',
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

    $this->admin = User::create([
        'id' => (string) Str::uuid(),
        'name' => 'Super Admin',
        'email' => 'admin_'.Str::random(6).'@owomarket.com',
        'password' => bcrypt('x'),
        'is_active' => true,
    ]);
    // `type` no es `fillable`: pasarlo a `create()` se descarta en silencio.
    $this->admin->type = 'super_admin';
    $this->admin->save();

    $this->avisos = app(NotificationDispatcher::class);
});

/** Una reclamación abierta hace `$dias` días. */
function reclamacionDeHace(string $tenantId, int $dias): CustomerReturnRequest
{
    $r = CustomerReturnRequest::create([
        'id' => (string) Str::uuid(),
        'order_id' => (string) Str::uuid(),
        'order_source' => 'central',
        'order_number' => 'ORD-PER-1',
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

    // `created_at` la pone Eloquent: para simular antigüedad hay que reescribirla.
    $r->forceFill(['created_at' => now()->subDays($dias)])->save();

    return $r->fresh();
}

// ---------------------------------------------------------------- Reclamación a punto de vencer

it('avisa de una reclamación a la que le queda un día', function () {
    /*
     * Es lo que convierte el reloj en justo: perder una venta por silencio tras dos avisos es una
     * decisión del comerciante; perderla con uno solo de hace cinco días es un descuido que la
     * plataforma podía haber evitado.
     */
    $plazo = app(ClaimResponseWindow::class)->days();
    reclamacionDeHace($this->tenant->id, $plazo - 1);

    $avisadas = app(RemindExpiringClaimsUseCase::class)->execute();

    expect($avisadas)->toBe(1);
    expect($this->dueno->notifications()->first()->data['type'])->toBe('claim.expiring');
});

it('no avisa de una reclamación que aún tiene días', function () {
    // Avisar el segundo día de cinco es avisar de nada, y gasta la atención que hará falta el
    // cuarto.
    reclamacionDeHace($this->tenant->id, 1);

    expect(app(RemindExpiringClaimsUseCase::class)->execute())->toBe(0);
    expect(Notification::count())->toBe(0);
});

it('el recordatorio no se repite aunque el comando corra cada día', function () {
    /*
     * EL TEST DEL FRENO. El comando corre cada madrugada y el estado no cambia hasta que alguien
     * conteste: sin freno, el mismo recordatorio saldría todos los días hasta vencer.
     */
    $plazo = app(ClaimResponseWindow::class)->days();
    reclamacionDeHace($this->tenant->id, $plazo - 1);

    app(RemindExpiringClaimsUseCase::class)->execute();
    app(RemindExpiringClaimsUseCase::class)->execute();
    app(RemindExpiringClaimsUseCase::class)->execute();

    expect($this->dueno->notifications()->count())->toBe(1);
});

it('una reclamación ya resuelta no recibe recordatorio', function () {
    $plazo = app(ClaimResponseWindow::class)->days();
    $r = reclamacionDeHace($this->tenant->id, $plazo - 1);
    $r->update(['status' => 'approved', 'resolved_at' => now(), 'resolved_by' => 'merchant']);

    $this->avisos->claimAboutToExpire($r->id);

    expect(Notification::count())->toBe(0);
});

it('el recordatorio dice qué evita responder', function () {
    // Un aviso que solo dice «vence» no ayuda: hay que decir que responder --aunque sea para
    // rechazarla-- evita perderla.
    $plazo = app(ClaimResponseWindow::class)->days();
    $r = reclamacionDeHace($this->tenant->id, $plazo - 1);

    $this->avisos->claimAboutToExpire($r->id);

    expect($this->dueno->notifications()->first()->data['body'])->toContain('aunque sea para rechazarla');
});

// ---------------------------------------------------------------- Techo mensual

/** Una cobertura que le costó `$dolares` a la plataforma este mes. */
function coberturaDelMes(string $tenantId, float $dolares, float $tasa = 800.0): void
{
    $tenantOrderId = (string) Str::uuid();

    PlatformCommission::create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $tenantId,
        'order_id' => $tenantOrderId,
        'order_number' => 'ORD-'.strtoupper(Str::random(5)),
        'order_total' => $dolares,
        'commission_rate' => 8.0,
        'commission_amount' => $dolares * 0.08,
        'currency' => 'USD',
        'exchange_rate' => $tasa,
        'status' => 'collected',
    ]);

    CustomerReturnRequest::create([
        'id' => (string) Str::uuid(),
        'order_id' => (string) Str::uuid(),
        'order_source' => 'central',
        'order_number' => 'ORD-COB-1',
        'tenant_order_id' => $tenantOrderId,
        'customer_id' => (string) Str::uuid(),
        'customer_email' => 'ana@example.com',
        'product_id' => (string) Str::uuid(),
        'product_name' => 'Producto',
        'tenant_id' => $tenantId,
        'reason' => 'Otro motivo',
        'description' => 'Da igual.',
        'status' => 'approved',
        'resolved_at' => now(),
        'resolved_by' => 'merchant',
        'platform_covered_amount' => $dolares * $tasa,
    ]);
}

function techoDe(float $dolares): void
{
    CentralSetting::create([
        'id' => (string) Str::uuid(),
        'group' => 'payment',
        'key' => 'central_claim_monthly_alarm_usd',
        'value' => (string) $dolares,
    ]);
}

it('avisa a la plataforma cuando el mes pasa del techo', function () {
    techoDe(100.0);
    coberturaDelMes($this->tenant->id, 300.0);

    $this->avisos->coverageCeilingExceeded();

    $aviso = $this->admin->notifications()->first()->data;

    expect($aviso['type'])->toBe('coverage.ceiling');
    // `(float)` porque el payload viaja por JSON y 300.0 vuelve como entero 300. Es el mismo
    // detalle que ya mordio en los tests de cobertura.
    expect((float) $aviso['spent_usd'])->toBe(300.0);
});

it('el aviso deja claro que NO se cortó ningún pago', function () {
    /*
     * El techo es una alarma, no un muro. Un aviso que no lo aclare hará que alguien salga
     * corriendo a desbloquear pagos que nunca se bloquearon.
     */
    techoDe(100.0);
    coberturaDelMes($this->tenant->id, 300.0);

    $this->avisos->coverageCeilingExceeded();

    expect($this->admin->notifications()->first()->data['body'])->toContain('No se ha cortado ningún pago');
});

it('no avisa si el mes no ha pasado del techo', function () {
    techoDe(1000.0);
    coberturaDelMes($this->tenant->id, 50.0);

    $this->avisos->coverageCeilingExceeded();

    expect(Notification::count())->toBe(0);
});

it('el aviso del techo sale una vez al mes, no una vez al día', function () {
    /*
     * EL OTRO TEST DEL FRENO, y el que más importa: una vez pasado el techo el mes sigue pasado
     * mañana y pasado mañana. Sin freno, el comando diario mandaría el mismo aviso hasta fin de
     * mes y dejaría de leerse justo cuando importa.
     */
    techoDe(100.0);
    coberturaDelMes($this->tenant->id, 300.0);

    $this->avisos->coverageCeilingExceeded();
    $this->avisos->coverageCeilingExceeded();
    $this->avisos->coverageCeilingExceeded();

    expect($this->admin->notifications()->count())->toBe(1);
});

it('los dos comandos corren sin reventar aunque no haya nada que avisar', function () {
    // Es su caso normal: la mayoría de los días no hay ni reclamación a punto de vencer ni techo
    // superado. Un comando programado que falla en su caso normal llena el log de ruido.
    $this->artisan('returns:remind-expiring')->assertSuccessful();
    $this->artisan('coverage:check-ceiling')->assertSuccessful();

    expect(Notification::count())->toBe(0);
});

it('los dos avisos de esta fase son críticos, y eso no se puede deshacer sin darse cuenta', function () {
    /*
     * `claim.expiring` es el ÚLTIMO aviso antes de que el reloj resuelva en contra: si el primero
     * es crítico, el último lo es más. Y `coverage.ceiling` es la «revisión obligatoria» que pide
     * la decisión de garantías — dejarla opcional sería volver al problema que vino a resolver:
     * el número estaba a la vista y nadie miraba.
     */
    expect(EmailDelivery::CRITICOS)->toContain('claim.expiring');
    expect(EmailDelivery::CRITICOS)->toContain('coverage.ceiling');
});
