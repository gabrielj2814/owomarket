<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Src\Monetization\Infrastructure\Eloquent\Models\SubscriptionPlan;
use Src\Monetization\Infrastructure\Eloquent\Models\TenantPlanChangeRequest;
use Src\Monetization\Infrastructure\Eloquent\Models\TenantSubscription;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;
use Src\Tenant\Infrastructure\Eloquent\Models\User;
use Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper;
use Stancl\Tenancy\Events\TenantCreated;
use Stancl\Tenancy\Events\TenantDeleted;

/*
|--------------------------------------------------------------------------
| Hallazgo T3 - solicitudes de cambio de plan
|--------------------------------------------------------------------------
|
| El boton "Mejorar Plan" de la pantalla de facturacion era un alert() que decia
| "Solicitud registrada. Un asesor te contactara" y NO mandaba nada: no existia endpoint ni
| tabla. El comerciante esperaba una llamada que nadie iba a hacer.
|
| El plan determina la `commission_rate`, o sea lo que la plataforma cobra por cada venta,
| asi que esto es un camino de dinero y se prueba como tal.
*/
beforeEach(function () {
    Event::fake([TenantCreated::class, TenantDeleted::class]);

    config([
        'tenancy.bootstrappers' => array_values(array_filter(
            config('tenancy.bootstrappers', []),
            fn ($b) => $b !== DatabaseTenancyBootstrapper::class
        )),
    ]);

    RateLimiter::clear('altas');
    cache()->flush();

    if (! Schema::hasTable('subscription_plans')) {
        (require base_path('database/migrations/2026_08_19_000003_create_monetization_tables.php'))->up();
    }
    if (! Schema::hasTable('tenant_plan_change_requests')) {
        (require base_path('database/migrations/2026_08_23_120000_create_tenant_plan_change_requests_table.php'))->up();
    }

    $this->planBasico = SubscriptionPlan::create([
        'id' => (string) Str::uuid(),
        'name' => 'Basico',
        'slug' => 'basico-'.bin2hex(random_bytes(3)),
        'price_monthly' => 0.00,
        'commission_rate' => 10.00,
        'max_products' => 50,
        'is_active' => true,
    ]);

    $this->planPro = SubscriptionPlan::create([
        'id' => (string) Str::uuid(),
        'name' => 'Pro',
        'slug' => 'pro-'.bin2hex(random_bytes(3)),
        'price_monthly' => 29.00,
        'commission_rate' => 5.00,
        'max_products' => 1000,
        'is_active' => true,
    ]);

    $this->duenio = User::create([
        'id' => (string) Str::uuid(),
        'name' => 'Comerciante T3',
        'email' => 'duenio_'.bin2hex(random_bytes(3)).'@example.com',
        'password' => bcrypt('OwO_12345678'),
        'type' => 'tenant_owner',
    ]);

    $this->tienda = Tenant::create([
        'id' => 't3_'.bin2hex(random_bytes(3)),
        'name' => 'Tienda T3',
        'slug' => 't3-'.bin2hex(random_bytes(3)),
        'status' => 'active',
        'request' => 'approved',
    ]);

    $this->tienda->users()->attach($this->duenio->id, ['id' => (string) Str::uuid(), 'role' => 'owner']);

    TenantSubscription::create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $this->tienda->id,
        'plan_id' => $this->planBasico->id,
        'billing_cycle' => 'monthly',
        'status' => 'active',
        'starts_at' => now(),
    ]);
});

function crearAdminT3(): User
{
    return User::create([
        'id' => (string) Str::uuid(),
        'name' => 'Super Admin T3',
        'email' => 'admin_'.bin2hex(random_bytes(4)).'@owomarket.local',
        'password' => bcrypt('OwO_12345678'),
        'type' => 'super_admin',
        'is_active' => true,
    ]);
}

test('el comerciante puede solicitar un cambio de plan (T3)', function () {
    $this->actingAs($this->duenio)
        ->postJson('/tenant/owner/api/plan-change-request', [
            'tenant_id' => $this->tienda->id,
            'plan_id' => $this->planPro->id,
        ])
        ->assertStatus(201);

    $solicitud = TenantPlanChangeRequest::first();
    expect($solicitud)->not->toBeNull();
    expect($solicitud->status)->toBe('pending');
    expect((string) $solicitud->requested_plan_id)->toBe((string) $this->planPro->id);

    // El plan de partida queda anotado: el panel muestra "de X a Y".
    expect((string) $solicitud->current_plan_id)->toBe((string) $this->planBasico->id);
});

test('no se puede solicitar un cambio sobre la tienda de otro comerciante (T3)', function () {
    $intruso = User::create([
        'id' => (string) Str::uuid(),
        'name' => 'Intruso',
        'email' => 'intruso_'.bin2hex(random_bytes(3)).'@example.com',
        'password' => bcrypt('OwO_12345678'),
        'type' => 'tenant_owner',
    ]);

    $this->actingAs($intruso)
        ->postJson('/tenant/owner/api/plan-change-request', [
            'tenant_id' => $this->tienda->id,
            'plan_id' => $this->planPro->id,
        ])
        ->assertStatus(403);

    expect(TenantPlanChangeRequest::count())->toBe(0);
});

test('no se acumulan dos solicitudes pendientes de la misma tienda (T3)', function () {
    $enviar = fn () => $this->actingAs($this->duenio)->postJson('/tenant/owner/api/plan-change-request', [
        'tenant_id' => $this->tienda->id,
        'plan_id' => $this->planPro->id,
    ]);

    $enviar()->assertStatus(201);

    // Pulsar el boton otra vez no debe generar una segunda: el administrador no sabria cual
    // resolver.
    $enviar()->assertStatus(409);

    expect(TenantPlanChangeRequest::count())->toBe(1);
});

test('pedir el plan que ya se tiene se rechaza (T3)', function () {
    $this->actingAs($this->duenio)
        ->postJson('/tenant/owner/api/plan-change-request', [
            'tenant_id' => $this->tienda->id,
            'plan_id' => $this->planBasico->id,
        ])
        ->assertStatus(422);
});

test('aprobar el cambio mueve la tienda al plan nuevo y cierra el anterior (T3)', function () {
    $admin = crearAdminT3();

    $this->actingAs($this->duenio)->postJson('/tenant/owner/api/plan-change-request', [
        'tenant_id' => $this->tienda->id,
        'plan_id' => $this->planPro->id,
    ])->assertStatus(201);

    $solicitud = TenantPlanChangeRequest::first();

    $this->actingAs($admin)
        ->postJson("/admin/api/plan-changes/{$solicitud->id}/approve")
        ->assertStatus(200);

    // La suscripcion vigente ahora es la del plan nuevo...
    $activa = TenantSubscription::where('tenant_id', $this->tienda->id)->where('status', 'active')->first();
    expect((string) $activa->plan_id)->toBe((string) $this->planPro->id);

    // ...y la anterior se cierra en vez de editarse, para que quede el historial de en que
    // plan estuvo la tienda y desde cuando. Eso es lo que hace auditable una comision pasada.
    $anterior = TenantSubscription::where('tenant_id', $this->tienda->id)
        ->where('plan_id', $this->planBasico->id)
        ->first();
    expect($anterior->status)->toBe('cancelled');
    expect($anterior->cancelled_at)->not->toBeNull();
});

test('no se aprueba un plan que se desactivo despues de solicitarlo (T3)', function () {
    // La leccion de T1: comprobar al pedir y no al ejecutar es exactamente el fallo que
    // dejaba pagar retiros sin respaldo. Con el plan viaja la commission_rate.
    $admin = crearAdminT3();

    $this->actingAs($this->duenio)->postJson('/tenant/owner/api/plan-change-request', [
        'tenant_id' => $this->tienda->id,
        'plan_id' => $this->planPro->id,
    ])->assertStatus(201);

    $this->planPro->update(['is_active' => false]);

    $solicitud = TenantPlanChangeRequest::first();

    $this->actingAs($admin)
        ->postJson("/admin/api/plan-changes/{$solicitud->id}/approve")
        ->assertStatus(422);

    expect($solicitud->fresh()->status)->toBe('pending');

    $activa = TenantSubscription::where('tenant_id', $this->tienda->id)->where('status', 'active')->first();
    expect((string) $activa->plan_id)->toBe((string) $this->planBasico->id);
});

test('rechazar exige un motivo y lo conserva (T3)', function () {
    $admin = crearAdminT3();

    $this->actingAs($this->duenio)->postJson('/tenant/owner/api/plan-change-request', [
        'tenant_id' => $this->tienda->id,
        'plan_id' => $this->planPro->id,
    ])->assertStatus(201);

    $solicitud = TenantPlanChangeRequest::first();

    // Sin motivo no pasa: una solicitud rechazada sin explicacion deja al comerciante sin
    // saber que corregir.
    $this->actingAs($admin)
        ->postJson("/admin/api/plan-changes/{$solicitud->id}/reject", [])
        ->assertStatus(422);

    $this->actingAs($admin)
        ->postJson("/admin/api/plan-changes/{$solicitud->id}/reject", [
            'rejection_reason' => 'Hay facturas pendientes de pago.',
        ])
        ->assertStatus(200);

    expect($solicitud->fresh()->status)->toBe('rejected');
    expect($solicitud->fresh()->rejection_reason)->toBe('Hay facturas pendientes de pago.');
});
