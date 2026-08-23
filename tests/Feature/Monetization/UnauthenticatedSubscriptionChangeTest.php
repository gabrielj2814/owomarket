<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Src\Monetization\Infrastructure\Eloquent\Models\SubscriptionPlan;
use Src\Monetization\Infrastructure\Eloquent\Models\TenantSubscription;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant as ModelsTenant;
use Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper;
use Stancl\Tenancy\Events\TenantCreated;
use Stancl\Tenancy\Events\TenantDeleted;

/*
|--------------------------------------------------------------------------
| Hallazgo T5 - cambiar el plan de una tienda SIN AUTENTICARSE
|--------------------------------------------------------------------------
|
| `POST /monetization/subscribe` esta en src/Monetization/.../Routes/tenant.php SIN ningun
| middleware: ni `auth`, ni comprobacion de propietario, ni limite de tasa. Lo unico que
| hay delante es la resolucion de tenancy por dominio.
|
| Asi que en el escaparate de cualquier tienda, un visitante anonimo puede cambiarle el
| plan de suscripcion — y con el plan viaja la `commission_rate`, o sea lo que la
| plataforma le cobra por cada venta. La propia respuesta lo dice: "Ahora disfrutas de una
| comision reducida".
|
| El caso realista no es el vandalo: es el comerciante regalandose el plan Pro sin pagarlo
| ni pedirlo. Y deja INUTIL el flujo de aprobacion de cambios de plan (hallazgo T3), porque
| existe una puerta que se lo salta entero.
*/
beforeEach(function () {
    Event::fake([TenantCreated::class, TenantDeleted::class]);

    config([
        'tenancy.bootstrappers' => array_values(array_filter(
            config('tenancy.bootstrappers', []),
            fn ($b) => $b !== DatabaseTenancyBootstrapper::class
        )),
    ]);

    if (! Schema::hasTable('subscription_plans')) {
        (require base_path('database/migrations/2026_08_19_000003_create_monetization_tables.php'))->up();
    }

    $this->planCaro = SubscriptionPlan::create([
        'id' => (string) Str::uuid(),
        'name' => 'Basico',
        'slug' => 'basico-'.bin2hex(random_bytes(3)),
        'price_monthly' => 0.00,
        'commission_rate' => 10.00,
        'max_products' => 50,
        'is_active' => true,
    ]);

    $this->planBarato = SubscriptionPlan::create([
        'id' => (string) Str::uuid(),
        'name' => 'Pro',
        'slug' => 'pro-'.bin2hex(random_bytes(3)),
        'price_monthly' => 29.00,
        'commission_rate' => 2.00,
        'max_products' => 5000,
        'is_active' => true,
    ]);

    $tenantId = 't5_'.bin2hex(random_bytes(3));
    $this->tenant = ModelsTenant::create([
        'id' => $tenantId,
        'name' => 'Tienda T5',
        'slug' => $tenantId,
        'status' => 'active',
        'request' => 'approved',
    ]);

    $this->domain = "{$tenantId}.localhost";
    $this->tenant->domains()->create([
        'id' => (string) Str::uuid(),
        'domain' => $this->domain,
    ]);

    TenantSubscription::create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $this->tenant->id,
        'plan_id' => $this->planCaro->id,
        'billing_cycle' => 'monthly',
        'status' => 'active',
        'starts_at' => now(),
    ]);
});

test('la ruta de auto-suscripcion ya no existe (T5)', function () {
    // Se borro en vez de protegerse: aunque exigiera sesion, seguiria permitiendo que una
    // tienda se cambie el plan por su cuenta, que es lo que el flujo de aprobacion del
    // hallazgo T3 existe para impedir.
    $respuesta = $this->postJson("http://{$this->domain}/monetization/subscribe", [
        'plan' => $this->planBarato->slug,
        'billing_cycle' => 'monthly',
    ]);

    expect($respuesta->status())->toBe(404);

    // Y la comision de la tienda sigue siendo la que era.
    $activa = TenantSubscription::where('tenant_id', $this->tenant->id)
        ->where('status', 'active')
        ->first();

    expect((string) $activa->plan_id)->toBe((string) $this->planCaro->id);
});

test('un anonimo NO puede reportar el pago de una liquidacion (T5)', function () {
    // Antes esto llegaba al controlador y solo fallaba por no encontrar el id inventado:
    // con un id real habria escrito la referencia bancaria de un desconocido sobre una
    // fila de dinero.
    $respuesta = $this->postJson("http://{$this->domain}/monetization/settlements/pay", [
        'settlement_id' => (string) Str::uuid(),
        'payment_method' => 'pago_movil',
        'payment_reference' => 'INVENTADA-123',
    ]);

    expect($respuesta->status())->toBeIn([401, 403, 419]);
});
