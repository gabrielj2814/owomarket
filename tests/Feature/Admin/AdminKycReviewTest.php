<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;
use Src\Tenant\Infrastructure\Eloquent\Models\TenantKycProfile;
use Src\Tenant\Infrastructure\Eloquent\Models\User;

/**
 * La revisión de identidad del backoffice (subsistema 1, vista 1 de
 * `planes/ESTADO_DEL_PROYECTO.md`).
 *
 * **El agujero que cierra:** `ReviewTenantKycUseCase` existía sin controlador ni ruta. El KYC
 * exige identidad verificada para solicitar un retiro y no había ninguna forma de verificar a
 * nadie, así que todos los expedientes se quedaban en `pending` para siempre y **ninguna
 * tienda de la plataforma podía cobrar**.
 *
 * Lo que se vigila aquí no es el formulario: es que el número de documento no salga por el
 * cable, que rechazar sin motivo no cuele, y que el orden de la lista ponga arriba lo que
 * lleva más tiempo bloqueando el dinero de alguien.
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
});

function tiendaConKyc(array $overrides = [], array $tenantOverrides = []): TenantKycProfile
{
    $tenant = Tenant::create(array_merge([
        'id' => 'shop-'.Str::random(6),
        'name' => 'Tienda '.Str::random(4),
        'slug' => 'tienda-'.Str::random(4),
        'status' => 'active',
        'request' => 'approved',
    ], $tenantOverrides));

    return TenantKycProfile::create(array_merge([
        'id' => (string) Str::uuid(),
        'tenant_id' => $tenant->id,
        'user_id' => (string) Str::uuid(),
        'legal_name' => 'María Pérez',
        'cedula' => 'V-12.345.678',
        'nationality' => 'V',
        'rif' => 'J-40123456-7',
        'phone' => '+58 412 1234567',
        'address' => 'Av. Principal, Caracas',
    ], $overrides));
}

it('verificar un expediente desbloquea el cobro de la tienda', function () {
    // EL TEST QUE IMPORTA. Sin esta ruta, `isVerified()` es false para siempre y la puerta
    // del retiro no se abre nunca para nadie.
    $perfil = tiendaConKyc();

    expect($perfil->isVerified())->toBeFalse();

    $this->actingAs($this->adminUser)
        ->postJson("/admin/api/kyc/profiles/{$perfil->id}/review", ['approved' => true])
        ->assertOk()
        ->assertJsonPath('data.status', 'verified');

    expect($perfil->fresh()->isVerified())->toBeTrue()
        ->and($perfil->fresh()->reviewed_by)->toBe($this->adminUser->id);
});

it('rechazar sin motivo no cuela, y el error señala el campo', function () {
    // El comerciante ve su cobro bloqueado; si no le decimos qué corregir, acaba en soporte
    // preguntando lo que la pantalla debería haberle dicho.
    $perfil = tiendaConKyc();

    $this->actingAs($this->adminUser)
        ->postJson("/admin/api/kyc/profiles/{$perfil->id}/review", ['approved' => false])
        ->assertStatus(422)
        ->assertJsonValidationErrors('reason');

    expect($perfil->fresh()->status)->toBe('pending');
});

it('rechazar con motivo lo deja escrito para el comerciante', function () {
    $perfil = tiendaConKyc();

    $this->actingAs($this->adminUser)
        ->postJson("/admin/api/kyc/profiles/{$perfil->id}/review", [
            'approved' => false,
            'reason' => 'La foto del documento está ilegible.',
        ])
        ->assertOk()
        ->assertJsonPath('data.status', 'rejected');

    expect($perfil->fresh()->rejection_reason)->toBe('La foto del documento está ilegible.');
});

it('la cédula y el RIF no salen por el cable', function () {
    // Están cifrados en reposo; devolverlos en un JSON desharía el cifrado por la puerta de
    // atrás --acabarían en un log, en una captura, en un ticket de soporte--.
    tiendaConKyc();

    $cuerpo = $this->actingAs($this->adminUser)
        ->getJson('/admin/api/kyc/profiles')
        ->assertOk()
        ->getContent();

    expect($cuerpo)->not->toContain('12345678')
        ->and($cuerpo)->not->toContain('12.345.678')
        ->and($cuerpo)->not->toContain('40123456')
        ->and($cuerpo)->not->toContain('cedula');
});

it('lo pendiente va primero y lo más viejo arriba', function () {
    // Cada expediente pendiente es una tienda que no cobra: el de arriba tiene que ser el que
    // lleva más tiempo esperando.
    $viejo = tiendaConKyc();
    $viejo->forceFill(['created_at' => now()->subDays(10)])->saveQuietly();

    $reciente = tiendaConKyc();
    $reciente->forceFill(['created_at' => now()->subDay()])->saveQuietly();

    $yaVisto = tiendaConKyc();
    $yaVisto->forceFill(['status' => 'verified', 'created_at' => now()->subDays(30)])->saveQuietly();

    $ids = collect($this->actingAs($this->adminUser)
        ->getJson('/admin/api/kyc/profiles')
        ->assertOk()
        ->json('data.profiles'))
        ->pluck('id')
        ->all();

    expect($ids)->toBe([$viejo->id, $reciente->id, $yaVisto->id]);
});

it('avisa de otras tiendas con la misma identidad, sin repetir el documento', function () {
    // La razón de ser del KYC: que la sanción sobreviva al cierre de la tienda. Sin esta
    // pregunta, quien quema una tienda abre otra y empieza limpio.
    $perfil = tiendaConKyc();
    tiendaConKyc(['rif' => 'J-40999999-9'], ['name' => 'La Segunda Tienda']);

    $respuesta = $this->actingAs($this->adminUser)
        ->getJson("/admin/api/kyc/profiles/{$perfil->id}/identity-matches")
        ->assertOk();

    $coincidencias = $respuesta->json('data');

    expect($coincidencias)->toHaveCount(1)
        ->and($coincidencias[0]['tenant_name'])->toBe('La Segunda Tienda');

    // El expediente consultado no se devuelve a sí mismo: si lo hiciera, la pantalla que se
    // olvide de filtrarlo diría que el comerciante tiene una tienda de más.
    expect(collect($coincidencias)->pluck('tenant_id'))->not->toContain($perfil->tenant_id);

    expect($respuesta->getContent())->not->toContain('12345678');
});

it('una identidad sin repetir no inventa coincidencias', function () {
    $perfil = tiendaConKyc();

    $this->actingAs($this->adminUser)
        ->getJson("/admin/api/kyc/profiles/{$perfil->id}/identity-matches")
        ->assertOk()
        ->assertJsonPath('data', []);
});

it('un expediente que no existe responde 404 y no 500', function () {
    $this->actingAs($this->adminUser)
        ->postJson('/admin/api/kyc/profiles/'.Str::uuid().'/review', ['approved' => true])
        ->assertStatus(404);
});

it('la pantalla arranca filtrando por pendientes, como dice su selector', function () {
    // Si la carga inicial no aplicara el mismo filtro que el selector trae seleccionado, la
    // primera pantalla mostraría TODOS los expedientes bajo una etiqueta que dice
    // «Pendientes»: el selector mentiría hasta que alguien lo tocara.
    tiendaConKyc();
    $verificada = tiendaConKyc();
    $verificada->forceFill(['status' => 'verified'])->saveQuietly();

    $props = $this->actingAs($this->adminUser)
        ->get("/admin/backoffice/{$this->adminUser->id}/kyc")
        ->assertOk()
        ->viewData('page')['props'];

    expect($props['filters']['status'])->toBe('pending')
        ->and($props['profiles'])->toHaveCount(1)
        ->and($props['profiles'][0]['status'])->toBe('pending');
});

it('sin sesión no se revisa nada', function () {
    $perfil = tiendaConKyc();

    $this->postJson("/admin/api/kyc/profiles/{$perfil->id}/review", ['approved' => true])
        ->assertStatus(401);

    expect($perfil->fresh()->status)->toBe('pending');
});
