<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant as ModelsTenant;
use Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper;
use Stancl\Tenancy\Events\TenantCreated;
use Stancl\Tenancy\Events\TenantDeleted;

/**
 * Fase 0.3-E — hallazgo A5.
 *
 * El grupo /api-tenant/* se montaba con el middleware 'api' (vacío en
 * Laravel 11+), así que las ~108 rutas del backoffice de cada tienda estaban
 * abiertas a internet. Este archivo fija el contrato nuevo: backoffice exige
 * sesión, y la lista blanca del storefront sigue siendo accesible sin ella.
 */
beforeEach(function () {
    Event::fake([TenantCreated::class, TenantDeleted::class]);

    config([
        'tenancy.bootstrappers' => array_values(array_filter(
            config('tenancy.bootstrappers', []),
            fn ($bootstrapper) => $bootstrapper !== DatabaseTenancyBootstrapper::class
        )),
    ]);

    $tenantId = 't_'.bin2hex(random_bytes(4));
    $this->tenant = ModelsTenant::create([
        'id' => $tenantId,
        'name' => 'Tienda Auth Test',
        'slug' => $tenantId,
        'status' => 'active',
        'request' => 'approved',
    ]);
    $this->domain = "{$tenantId}.localhost";
    $this->tenant->domains()->create([
        'id' => (string) Str::uuid(),
        'domain' => $this->domain,
    ]);
});

test('El backoffice del inquilino rechaza peticiones sin sesión', function () {
    // El escenario textual de la auditoría: crear un cupón del 100% sin login.
    $this->postJson("http://{$this->domain}/api-tenant/coupon/create", [
        'code' => 'FREE',
        'type' => 'percentage',
        'value' => 100,
    ])->assertStatus(401);

    // Borrar el catálogo entero sin login.
    $this->deleteJson("http://{$this->domain}/api-tenant/product/".Str::uuid())
        ->assertStatus(401);

    // Leer la base de clientes de la tienda.
    $this->postJson("http://{$this->domain}/api-tenant/customer/filter", [])
        ->assertStatus(401);

    // Leer la facturación de la tienda.
    $this->getJson("http://{$this->domain}/api-tenant/billing/metrics")
        ->assertStatus(401);

    // Cambiar la configuración de la tienda.
    $this->putJson("http://{$this->domain}/api-tenant/settings", [])
        ->assertStatus(401);

    // Moderar reseñas (aprobar la que sea).
    $this->postJson("http://{$this->domain}/api-tenant/review/".Str::uuid().'/moderate', [])
        ->assertStatus(401);

    // Procesar un pago.
    $this->postJson("http://{$this->domain}/api-tenant/payment/process", [])
        ->assertStatus(401);
});

test('La lista blanca del storefront sigue siendo accesible sin sesión', function () {
    // Consultar la sesión del comprador: responde, no exige sesión previa.
    $this->getJson("http://{$this->domain}/api-tenant/customer/auth/session")
        ->assertStatus(200)
        ->assertJsonPath('data.authenticated', false);

    // Validar un cupón desde el carrito: no debe dar 401. El código no existe,
    // así que responde con error de negocio (4xx que no es 401), no con
    // rechazo de autenticación.
    $couponResponse = $this->postJson("http://{$this->domain}/api-tenant/coupon/validate", [
        'code' => 'NOEXISTE',
        'order_subtotal' => 100.0,
    ]);
    expect($couponResponse->getStatusCode())->not->toBe(401);

    // Publicar una reseña: tampoco debe dar 401 (queda pública a propósito;
    // ver hallazgo B2 pendiente en el archivo de rutas de Review).
    $reviewResponse = $this->postJson("http://{$this->domain}/api-tenant/review/create", []);
    expect($reviewResponse->getStatusCode())->not->toBe(401);
});
