<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Src\Monetization\Infrastructure\Eloquent\Models\PlatformCommission;
use Src\Tenant\Application\UseCase\CreateTenantOwnerPayoutRequestUseCase;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;
use Src\Tenant\Infrastructure\Eloquent\Models\TenantKycProfile;
use Src\Tenant\Infrastructure\Eloquent\Models\User;

/**
 * Sin identidad verificada no sale dinero (subsistema 1).
 *
 * Es la **única** puerta donde se exige el KYC, y está aquí a propósito. Pedirlo en el alta
 * pondría toda la fricción antes de que el comerciante haya visto ningún valor, y una
 * plataforma que todavía tiene que llenarse de tiendas no se lo puede permitir. Aquí ya ha
 * vendido: el incentivo para completar el formulario es su propio dinero.
 *
 * Y es lo que hace que las sanciones del subsistema 5 signifiquen algo. Sin una identidad
 * detrás del dinero, una tienda que acumula deuda o cae a nivel bajo se registra otra vez con
 * otro nombre y empieza limpia.
 */
beforeEach(function () {
    $this->tenant = Tenant::create([
        'id' => 'shop-'.Str::random(6),
        'name' => 'Tienda Puerta',
        'slug' => 'puerta-'.Str::random(4),
        'status' => 'active',
        'request' => 'approved',
    ]);

    $this->owner = User::create([
        'id' => (string) Str::uuid(),
        'name' => 'Dueño',
        'email' => 'p_'.Str::random(6).'@owomarket.com',
        'password' => bcrypt('password123'),
        'type' => 'tenant_owner',
        'is_active' => true,
    ]);
    $this->tenant->users()->attach($this->owner->id, ['id' => (string) Str::uuid(), 'role' => 'owner']);

    // Saldo de sobra: lo único que puede frenar el retiro en estos tests es el KYC.
    PlatformCommission::create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $this->tenant->id,
        'order_id' => (string) Str::uuid(),
        'central_order_id' => (string) Str::uuid(),
        'order_number' => 'ORD-'.strtoupper(Str::random(6)),
        'order_total' => 100.0,
        'commission_rate' => 8.00,
        'commission_amount' => 8.00,
        'currency' => 'USD',
        'exchange_rate' => 50.0,
        'status' => 'pending',
        'released_at' => now()->subDays(30),
        'payment_gateway' => 'pago_movil',
    ]);

    $this->pedirRetiro = fn () => app(CreateTenantOwnerPayoutRequestUseCase::class)->execute(
        $this->owner->id,
        [
            'tenant_id' => $this->tenant->id,
            'amount' => 1000.0,
            'payment_method' => 'Pago Móvil',
            'payment_details' => ['bank' => 'Banesco'],
        ]
    );
});

function kycDe(Tenant $tenant, User $owner, string $status, ?string $motivo = null): TenantKycProfile
{
    return TenantKycProfile::create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $tenant->id,
        'user_id' => $owner->id,
        'legal_name' => 'Dueño Real',
        'cedula' => 'V-18765432',
        'phone' => '+58 412 1112233',
        'address' => 'Caracas',
        'status' => $status,
        'rejection_reason' => $motivo,
    ]);
}

it('sin expediente de identidad no se puede retirar', function () {
    // EL TEST QUE IMPORTA: no comprueba un mensaje, comprueba que el dinero no sale.
    expect($this->pedirRetiro)->toThrow(Exception::class);
});

it('con la verificación en revisión tampoco', function () {
    kycDe($this->tenant, $this->owner, 'pending');

    expect($this->pedirRetiro)->toThrow(Exception::class);
});

it('con la verificación rechazada tampoco', function () {
    kycDe($this->tenant, $this->owner, 'rejected', 'La dirección no coincide.');

    expect($this->pedirRetiro)->toThrow(Exception::class);
});

it('con la identidad verificada el retiro sale', function () {
    kycDe($this->tenant, $this->owner, 'verified');

    $retiro = ($this->pedirRetiro)();

    expect($retiro->tenant_id)->toBe($this->tenant->id)
        ->and($retiro->currency)->toBe('VES');
});

it('el motivo del rechazo llega al comerciante', function () {
    // Si no, ve su cobro bloqueado sin saber qué corregir y acaba en soporte preguntando lo
    // que la pantalla debería haberle dicho.
    kycDe($this->tenant, $this->owner, 'rejected', 'La dirección no coincide.');

    try {
        ($this->pedirRetiro)();
        expect(false)->toBeTrue('Debería haber lanzado excepción.');
    } catch (Exception $e) {
        expect($e->getMessage())->toContain('La dirección no coincide');
    }
});
