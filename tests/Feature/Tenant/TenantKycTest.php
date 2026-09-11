<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Src\Admin\Application\UseCase\FindTenantsByIdentityUseCase;
use Src\Admin\Application\UseCase\ReviewTenantKycUseCase;
use Src\Tenant\Infrastructure\Eloquent\Models\TenantKycProfile;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;

/**
 * KYC del comerciante (subsistema 1 de
 * `planes/anotaciones/DECISION_GARANTIAS_Y_RESPONSABILIDAD.md`).
 *
 * Dos cosas se vigilan aquí, y ninguna es el formulario:
 *
 * 1. **Que los documentos estén cifrados en reposo.** Guardar cédulas y RIF es un pasivo, no
 *    un activo: si se filtran, el daño es a personas reales. El test lee la columna cruda para
 *    comprobar que ahí no está el número.
 *
 * 2. **Que aun cifrados se puedan buscar.** La decisión dice que el valor principal del KYC no
 *    es la denuncia sino que la sanción sobreviva al cierre de la tienda — y eso exige poder
 *    preguntar «¿esta cédula ya tenía una tienda?». Cifrar sin hash haría esa pregunta
 *    imposible de responder, que es el error fácil de cometer aquí.
 */
beforeEach(function () {
    $this->tenant = Tenant::create([
        'id' => 'shop-'.Str::random(6),
        'name' => 'Tienda KYC',
        'slug' => 'kyc-'.Str::random(4),
        'status' => 'active',
        'request' => 'approved',
    ]);
});

function perfilKyc(Tenant $tenant, array $overrides = []): TenantKycProfile
{
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

it('la cédula y el RIF se guardan cifrados', function () {
    $perfil = perfilKyc($this->tenant);

    $crudo = DB::table('tenant_kyc_profiles')->where('id', $perfil->id)->first();

    // Lo que hay en disco no puede ser el número. Si este test se pone rojo, hay documentos de
    // identidad en texto plano en la base de datos.
    expect($crudo->cedula)->not->toContain('12345678')
        ->and($crudo->cedula)->not->toContain('12.345.678')
        ->and($crudo->rif)->not->toContain('40123456');

    // Y se leen bien a través del modelo, que es el único que sabe descifrarlos.
    expect($perfil->fresh()->cedula)->toBe('V-12.345.678');
});

it('el hash acompaña siempre al dato, sin tener que acordarse', function () {
    // Se pone en `saving`, no en quien llama: un expediente guardado sin hash dejaría la
    // búsqueda por identidad sin nada que mirar, y nadie se enteraría hasta que hiciera falta.
    $perfil = perfilKyc($this->tenant);

    $crudo = DB::table('tenant_kyc_profiles')->where('id', $perfil->id)->first();

    expect($crudo->cedula_hash)->toHaveLength(64)
        ->and($crudo->rif_hash)->toHaveLength(64);
});

it('encuentra otras tiendas de la misma identidad', function () {
    // EL TEST QUE IMPORTA. Sin esto, quien quema una tienda abre otra y empieza limpio.
    perfilKyc($this->tenant);

    $otra = Tenant::create([
        'id' => 'shop-'.Str::random(6),
        'name' => 'Tienda Nueva',
        'slug' => 'nueva-'.Str::random(4),
        'status' => 'active',
        'request' => 'approved',
    ]);
    perfilKyc($otra, ['legal_name' => 'María Pérez', 'rif' => 'J-40999999-9']);

    $encontradas = app(FindTenantsByIdentityUseCase::class)->execute(cedula: 'V-12.345.678');

    expect($encontradas)->toHaveCount(2);
});

it('el mismo documento escrito de otra forma sigue siendo el mismo', function () {
    // «V-12.345.678» y «12345678» son la misma persona. Sin normalizar, el bloqueo se esquiva
    // con un guion.
    perfilKyc($this->tenant);

    expect(app(FindTenantsByIdentityUseCase::class)->execute(cedula: '12345678'))->toHaveCount(1);
    expect(app(FindTenantsByIdentityUseCase::class)->execute(cedula: 'v12.345.678'))->toHaveCount(1);
});

it('la búsqueda no devuelve los documentos', function () {
    // Quien consulta ya sabe por cuál buscó. Repetirlos en la respuesta solo multiplicaría los
    // sitios donde pueden acabar: un log, una captura, un ticket de soporte.
    perfilKyc($this->tenant);

    $fila = app(FindTenantsByIdentityUseCase::class)->execute(cedula: 'V-12.345.678')[0];

    expect($fila)->not->toHaveKey('cedula')
        ->and($fila)->not->toHaveKey('rif');
});

it('los documentos no se escapan al serializar', function () {
    // Sin `$hidden`, cualquier `return $perfil` de un controlador los devolvería descifrados en
    // el JSON, que es la forma más tonta de deshacer el cifrado.
    $json = perfilKyc($this->tenant)->toArray();

    expect($json)->not->toHaveKey('cedula')
        ->and($json)->not->toHaveKey('rif')
        ->and($json)->not->toHaveKey('cedula_hash');
});

it('un expediente nace pendiente de revisión', function () {
    expect(perfilKyc($this->tenant)->status)->toBe('pending');
});

it('el administrador verifica el expediente', function () {
    $perfil = perfilKyc($this->tenant);

    app(ReviewTenantKycUseCase::class)->execute($perfil->id, 'admin-1', aprobado: true);

    $perfil = $perfil->fresh();
    expect($perfil->status)->toBe('verified')
        ->and($perfil->isVerified())->toBeTrue()
        ->and($perfil->reviewed_by)->toBe('admin-1');
});

it('rechazar exige motivo', function () {
    // Sin motivo, el comerciante ve su cobro bloqueado y no sabe qué corregir: acaba en
    // soporte preguntando lo que la pantalla debería haberle dicho.
    $perfil = perfilKyc($this->tenant);

    expect(fn () => app(ReviewTenantKycUseCase::class)->execute($perfil->id, 'admin-1', aprobado: false))
        ->toThrow(Exception::class);

    expect($perfil->fresh()->status)->toBe('pending');
});

it('un rechazo guarda su motivo', function () {
    $perfil = perfilKyc($this->tenant);

    app(ReviewTenantKycUseCase::class)->execute($perfil->id, 'admin-1', aprobado: false, motivo: 'La foto no se lee.');

    expect($perfil->fresh()->status)->toBe('rejected')
        ->and($perfil->fresh()->rejection_reason)->toBe('La foto no se lee.');
});
