<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CustomerReturnRequest;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;
use Src\Tenant\Infrastructure\Eloquent\Models\TenantKycProfile;
use Src\Tenant\Infrastructure\Eloquent\Models\User;

/**
 * La mesa de reclamaciones del backoffice y su expediente (subsistema 5, fase D · vista 5 de
 * `planes/ESTADO_DEL_PROYECTO.md`).
 *
 * **El agujero que cierra:** `BuildClaimDossierUseCase` existía sin controlador ni ruta, y
 * además pedía un `claimId` que nadie podía obtener porque ninguna pantalla central listaba
 * reclamaciones. Era código inalcanzable por partida doble.
 *
 * Lo que se vigila: que el listado no filtre datos de identidad que no necesita, que el
 * expediente y su PDF entreguen lo que se decidió entregar, y que el PDF salga de verdad.
 */
beforeEach(function () {
    $this->adminUser = User::create([
        'id' => (string) Str::uuid(),
        'name' => 'Super Admin',
        'email' => 'admin_'.Str::random(6).'@owomarket.com',
        'password' => bcrypt('password123'),
        'type' => 'super_admin',
        'is_active' => true,
    ]);

    $this->tenant = Tenant::create([
        'id' => 'shop-'.Str::random(6),
        'name' => 'Tienda del Expediente',
        'slug' => 'exped-'.Str::random(4),
        'status' => 'active',
        'request' => 'approved',
    ]);

    TenantKycProfile::create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $this->tenant->id,
        'legal_name' => 'María Pérez',
        'cedula' => 'V-12345678',
        'nationality' => 'V',
        'rif' => 'J-401234567',
        'phone' => '+58 412 1234567',
        'address' => 'Av. Principal, Caracas',
        'status' => 'verified',
    ]);
});

function reclamacionDeExpediente(Tenant $tenant, array $overrides = []): CustomerReturnRequest
{
    $creadaEl = $overrides['created_at'] ?? null;
    unset($overrides['created_at']);

    $reclamacion = CustomerReturnRequest::create(array_merge([
        'id' => (string) Str::uuid(),
        'order_id' => (string) Str::uuid(),
        'order_number' => 'ORD-'.strtoupper(Str::random(5)),
        'tenant_order_id' => (string) Str::uuid(),
        'customer_id' => (string) Str::uuid(),
        'customer_email' => 'cliente@example.com',
        'product_id' => (string) Str::uuid(),
        'product_name' => 'Auriculares',
        'amount' => 45.50,
        'tenant_id' => $tenant->id,
        'reason' => 'defectuoso',
        'description' => 'Llegó sin sonido.',
        'status' => 'requested',
    ], $overrides));

    if ($creadaEl !== null) {
        $reclamacion->forceFill(['created_at' => $creadaEl])->saveQuietly();
    }

    return $reclamacion->fresh();
}

it('el listado trae las reclamaciones con el nombre de su tienda', function () {
    reclamacionDeExpediente($this->tenant);

    $fila = $this->actingAs($this->adminUser)
        ->getJson('/admin/api/claims')
        ->assertOk()
        ->json('data.claims.0');

    expect($fila['tenant_name'])->toBe('Tienda del Expediente')
        ->and($fila['is_open'])->toBeTrue();
});

it('el listado NO lleva datos de identidad de nadie', function () {
    // Se consulta muchas veces y casi siempre sin necesitarlos. Que el dato sensible viaje
    // solo en el expediente --una peticion deliberada-- es justamente la separacion.
    reclamacionDeExpediente($this->tenant);

    $cuerpo = $this->actingAs($this->adminUser)
        ->getJson('/admin/api/claims')
        ->assertOk()
        ->getContent();

    expect($cuerpo)->not->toContain('12345678')
        ->and($cuerpo)->not->toContain('401234567')
        ->and($cuerpo)->not->toContain('María Pérez');
});

it('las abiertas van primero y lo más viejo arriba', function () {
    $vieja = reclamacionDeExpediente($this->tenant, ['created_at' => now()->subDays(10)]);
    $reciente = reclamacionDeExpediente($this->tenant, ['created_at' => now()->subDay()]);
    $cerrada = reclamacionDeExpediente($this->tenant, ['status' => 'approved', 'resolved_by' => 'timeout']);
    $cerrada->forceFill(['created_at' => now()->subDays(30)])->saveQuietly();

    $ids = collect($this->actingAs($this->adminUser)
        ->getJson('/admin/api/claims?status=all')
        ->assertOk()
        ->json('data.claims'))
        ->pluck('id')
        ->all();

    expect($ids)->toBe([$vieja->id, $reciente->id, $cerrada->id]);
});

it('el expediente entrega la identidad completa de la tienda', function () {
    /*
     * pendiente-abogado: decision del 11/09/2026, con el proyecto en desarrollo. Se entrega
     * todo lo que hay porque sin identidad completa una denuncia no tiene contra quien
     * dirigirse. Cuando llegue la lista de campos a quitar del abogado, el unico sitio que
     * tocar es el bloque `store` de `BuildClaimDossierUseCase`.
     */
    $reclamacion = reclamacionDeExpediente($this->tenant);

    $tienda = $this->actingAs($this->adminUser)
        ->getJson("/admin/api/claims/{$reclamacion->id}/dossier")
        ->assertOk()
        ->json('data.store');

    expect($tienda['cedula'])->toBe('V-12345678')
        ->and($tienda['rif'])->toBe('J-401234567')
        ->and($tienda['legal_name'])->toBe('María Pérez')
        ->and($tienda['kyc_status'])->toBe('verified');
});

it('una reclamación inexistente da 404 y no un expediente vacío', function () {
    // Un expediente en blanco es peor que un error: parece un caso sin pruebas.
    $this->actingAs($this->adminUser)
        ->getJson('/admin/api/claims/'.Str::uuid().'/dossier')
        ->assertStatus(404);
});

it('el expediente sale en PDF descargable', function () {
    // Su destino es acompañar una denuncia, así que tiene que poder salir de la pantalla.
    $reclamacion = reclamacionDeExpediente($this->tenant);

    $respuesta = $this->actingAs($this->adminUser)
        ->get("/admin/api/claims/{$reclamacion->id}/dossier.pdf")
        ->assertOk();

    expect($respuesta->headers->get('content-type'))->toContain('application/pdf')
        ->and($respuesta->headers->get('content-disposition'))->toContain('attachment')
        // Un PDF de verdad empieza por su firma. Sin esto, una plantilla rota que devolviera
        // texto pasaria el test igual.
        ->and(substr($respuesta->getContent(), 0, 4))->toBe('%PDF');
});

it('sin sesión no se abre ningún expediente', function () {
    $reclamacion = reclamacionDeExpediente($this->tenant);

    $this->getJson("/admin/api/claims/{$reclamacion->id}/dossier")->assertStatus(401);
    $this->getJson('/admin/api/claims')->assertStatus(401);
});

it('la pantalla arranca filtrando por abiertas, como dice su selector', function () {
    reclamacionDeExpediente($this->tenant);
    $cerrada = reclamacionDeExpediente($this->tenant, ['status' => 'approved']);

    $props = $this->actingAs($this->adminUser)
        ->get("/admin/backoffice/{$this->adminUser->id}/claims")
        ->assertOk()
        ->viewData('page')['props'];

    expect($props['filters']['status'])->toBe('open')
        ->and($props['claims'])->toHaveCount(1)
        ->and(collect($props['claims'])->pluck('id'))->not->toContain($cerrada->id);
});
