<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Src\Admin\Application\UseCase\BuildClaimDossierUseCase;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CustomerReturnRequest;
use Src\Monetization\Infrastructure\Eloquent\Models\OrderDeliveryConfirmation;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;
use Src\Tenant\Infrastructure\Eloquent\Models\TenantKycProfile;

/**
 * El expediente de una reclamación (subsistema 5, fase D).
 *
 * Es la última capa del escalado y **casi nunca recupera el dinero**: los importes son pequeños
 * y el proceso lento. Su valor está en disuadir —una tienda que sabe que ignorar genera un
 * expediente con su identidad verificada dentro se comporta distinto— y en cerrar el proceso
 * con dignidad para el comprador.
 *
 * No construye nada: junta lo que los subsistemas 1, 3 y 5 ya guardaron. **Que esto sea una
 * simple lectura es la señal de que las piezas anteriores quedaron bien puestas.**
 */
beforeEach(function () {
    $this->tenant = Tenant::create([
        'id' => 'shop-'.Str::random(6),
        'name' => 'Tienda Expediente',
        'slug' => 'exp-'.Str::random(4),
        'status' => 'active',
        'request' => 'approved',
    ]);

    $this->orderId = (string) Str::uuid();

    TenantKycProfile::create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $this->tenant->id,
        'legal_name' => 'María Pérez',
        'cedula' => 'V-12345678',
        'rif' => 'J-401234567',
        'phone' => '+58 412 1234567',
        'address' => 'Av. Principal, Caracas',
        'status' => 'verified',
    ]);

    OrderDeliveryConfirmation::create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $this->tenant->id,
        'order_id' => $this->orderId,
        'declared_delivered_at' => now()->subDays(20),
        'released_at' => now()->subDays(13),
        'released_by' => 'timeout',
        'shipment_evidence' => [['url' => '/storage/delivery-evidence/paquete.jpg', 'type' => 'image']],
    ]);

    $this->claim = CustomerReturnRequest::create([
        'id' => (string) Str::uuid(),
        'order_id' => (string) Str::uuid(),
        'order_number' => 'ORD-EXP-1',
        'tenant_order_id' => $this->orderId,
        'customer_id' => (string) Str::uuid(),
        'customer_email' => 'comprador@example.com',
        'product_id' => (string) Str::uuid(),
        'product_name' => 'Producto',
        'amount' => 100.0,
        'tenant_id' => $this->tenant->id,
        'reason' => 'defectuoso',
        'description' => 'Llegó roto.',
        'status' => 'approved',
        'resolved_by' => 'timeout',
        'resolved_at' => now()->subDays(2),
        'platform_covered_amount' => 500.0,
    ]);
});

it('el expediente junta reclamación, identidad y entrega', function () {
    $dossier = app(BuildClaimDossierUseCase::class)->execute($this->claim->id);

    expect($dossier['claim']['order_number'])->toBe('ORD-EXP-1')
        ->and($dossier['store']['legal_name'])->toBe('María Pérez')
        ->and($dossier['store']['kyc_status'])->toBe('verified')
        ->and($dossier['delivery']['shipment_evidence'])->toHaveCount(1);
});

it('el expediente entrega la identidad completa del comerciante', function () {
    /*
     * pendiente-abogado: ESTE TEST SUSTITUYE A SU CONTRARIO, Y ESO NO ES UN DESCUIDO.
     *
     * Hasta el 11/09/2026 aquí se comprobaba que la cédula y el RIF NO salieran del
     * expediente. La decisión de esa fecha, con el proyecto en desarrollo y sin usuarios
     * reales, fue entregar todo lo que hay: sin identidad completa, una denuncia no tiene
     * contra quién dirigirse.
     *
     * La pregunta de qué se le puede entregar legalmente a un comprador que denuncia SIGUE
     * ABIERTA. Cuando llegue la respuesta del abogado como una lista de campos a quitar, el
     * único sitio que tocar es el bloque `store` de `BuildClaimDossierUseCase`, y este test
     * se ajusta con él.
     *
     * Se deja escrito aquí y no solo en el código porque **un test que se invierte sin
     * explicación es indistinguible de uno que alguien rompió y "arregló"**.
     */
    $dossier = app(BuildClaimDossierUseCase::class)->execute($this->claim->id);

    expect($dossier['store']['cedula'])->toBe('V-12345678')
        ->and($dossier['store']['rif'])->toBe('J-401234567')
        ->and($dossier['store']['legal_name'])->toBe('María Pérez')
        ->and($dossier['store']['phone'])->toBe('+58 412 1234567')
        ->and($dossier['store']['address'])->toBe('Av. Principal, Caracas');
});

it('el documento del comerciante sigue cifrado en la base de datos', function () {
    // Que el expediente lo ENTREGUE no significa que se guarde en claro. El cifrado en reposo
    // no se toca: lo que cambió es quién puede leerlo a través de una puerta con permiso, no
    // cómo está escrito en disco. Si este test se pone rojo, hay cédulas en texto plano.
    $crudo = DB::table('tenant_kyc_profiles')->where('tenant_id', $this->tenant->id)->first();

    expect($crudo->cedula)->not->toContain('12345678')
        ->and($crudo->rif)->not->toContain('401234567');
});

it('el expediente trae la cronología completa', function () {
    // Cuándo se entregó, cuándo se reclamó y cuándo se resolvió. Sin fechas no hay caso que
    // defender.
    $dossier = app(BuildClaimDossierUseCase::class)->execute($this->claim->id);

    expect($dossier['claim']['claimed_at'])->not->toBeNull()
        ->and($dossier['claim']['resolved_at'])->not->toBeNull()
        ->and($dossier['claim']['resolved_by'])->toBe('timeout')
        ->and($dossier['delivery']['declared_delivered_at'])->not->toBeNull();
});

it('el expediente dice cuánto puso la plataforma y en qué nivel está la tienda', function () {
    $dossier = app(BuildClaimDossierUseCase::class)->execute($this->claim->id);

    expect($dossier['claim']['platform_covered_amount'])->toBe(500.0)
        // La tienda ignoró esta reclamación, así que su nivel lo refleja.
        ->and($dossier['store']['reputation']['level'])->toBe('bajo')
        ->and($dossier['store']['reputation']['unanswered_claims'])->toBe(1);
});

it('una reclamación inexistente no devuelve un expediente vacío', function () {
    expect(fn () => app(BuildClaimDossierUseCase::class)->execute((string) Str::uuid()))
        ->toThrow(Exception::class);
});
