<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CentralCustomer;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CustomerReturnRequest;
use Src\Monetization\Infrastructure\Eloquent\Models\CommissionSettlement;
use Src\Monetization\Infrastructure\Eloquent\Models\SubscriptionPlan;
use Src\Monetization\Infrastructure\Eloquent\Models\TenantPlanChangeRequest;
use Src\Notification\Application\Contracts\NotificationDispatcher;
use Src\Notification\Infrastructure\Eloquent\Models\Notification;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;
use Src\Tenant\Infrastructure\Eloquent\Models\TenantKycProfile;
use Src\User\Infrastructure\Eloquent\Models\User;
use Stancl\Tenancy\Events\TenantCreated;
use Stancl\Tenancy\Events\TenantDeleted;

/**
 * El catálogo de avisos de la fase 2.
 *
 * Lo que se vigila no es que la fila exista —eso ya lo cubre `NotificationInboxTest`— sino **lo
 * que el aviso dice**, porque en varios de estos el texto equivocado cuesta dinero o confianza:
 *
 * - Decirle «la tienda aceptó» a quien ganó por silencio le atribuye a la tienda una decisión
 *   que no tomó.
 * - Decirle «rechazado» a un comerciante que acaba de cobrar es leer mal el estado.
 * - Un rechazo sin motivo deja a quien lo recibe sin saber qué corregir.
 */
beforeEach(function () {
    Event::fake([TenantCreated::class, TenantDeleted::class]);

    $this->tenant = Tenant::create([
        'id' => 'tienda-catalogo',
        'name' => 'Bazar Central',
        'slug' => 'bazar-central',
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
    /*
     * `type` NO esta en `$fillable` de este modelo, asi que pasarlo a `create()` se descarta en
     * silencio y el usuario nace sin tipo. Se asigna aparte, igual que hace
     * `CreateSuperAdminCommand`. Sin esto, `platformAdmins()` no encuentra a nadie y los avisos
     * a la plataforma se pierden sin error.
     */
    $this->admin->type = 'super_admin';
    $this->admin->save();

    $this->comprador = CentralCustomer::create([
        'id' => (string) Str::uuid(),
        'name' => 'Ana Compradora',
        'email' => 'ana_'.Str::random(5).'@example.com',
        'password' => 'secret',
    ]);

    $this->avisos = app(NotificationDispatcher::class);
});

function reclamacionResuelta(string $tenantId, string $customerId, string $estado, ?string $resueltaPor, ?string $notas = null): CustomerReturnRequest
{
    return CustomerReturnRequest::create([
        'id' => (string) Str::uuid(),
        'order_id' => (string) Str::uuid(),
        'order_source' => 'central',
        'order_number' => 'ORD-CAT-1',
        'tenant_order_id' => (string) Str::uuid(),
        'customer_id' => $customerId,
        'customer_email' => 'ana@example.com',
        'product_id' => (string) Str::uuid(),
        'product_name' => 'Auriculares',
        'tenant_id' => $tenantId,
        'reason' => 'Producto dañado o roto',
        'description' => 'Llegaron rotos.',
        'status' => $estado,
        'resolved_at' => now(),
        'resolved_by' => $resueltaPor,
        'resolution_notes' => $notas,
    ]);
}

it('una aprobación por silencio no se le atribuye a la tienda', function () {
    /*
     * EL TEXTO QUE IMPORTA. «La tienda aceptó tu reclamación» cuando la tienda nunca contestó le
     * atribuye una decisión que no tomó — y contradice la explicación de que venció el plazo. Es
     * el mismo error que ya se corrigió en la pantalla del comprador.
     */
    $r = reclamacionResuelta($this->tenant->id, $this->comprador->id, 'approved', 'timeout');

    $this->avisos->claimResolved($r->id);

    $alComprador = $this->comprador->notifications()->first()->data;

    expect($alComprador['body'])->not->toContain('La tienda aceptó');
    expect($alComprador['body'])->toContain('no respondió dentro del plazo');
});

it('una aprobación de la tienda sí dice que la aceptó ella', function () {
    $r = reclamacionResuelta($this->tenant->id, $this->comprador->id, 'approved', 'merchant');

    $this->avisos->claimResolved($r->id);

    expect($this->comprador->notifications()->first()->data['body'])->toContain('La tienda aceptó');
});

it('el comerciante se entera cuando pierde por silencio, y solo entonces', function () {
    // Si respondió él ya sabe lo que decidió. Enterarse de que perdió una venta por no contestar
    // es lo único que hace que la siguiente sí se conteste.
    $porSilencio = reclamacionResuelta($this->tenant->id, $this->comprador->id, 'approved', 'timeout');
    $this->avisos->claimResolved($porSilencio->id);

    expect($this->dueno->notifications()->count())->toBe(1);
    expect($this->dueno->notifications()->first()->data['type'])->toBe('claim.timedout');

    $porElComerciante = reclamacionResuelta($this->tenant->id, $this->comprador->id, 'approved', 'merchant');
    $this->avisos->claimResolved($porElComerciante->id);

    // Sigue habiendo uno solo: el segundo no genera aviso para la tienda.
    expect($this->dueno->notifications()->count())->toBe(1);
});

it('un rechazo lleva su motivo', function () {
    // Sin motivo, el comprador no sabe si rebatirlo o aceptarlo.
    $r = reclamacionResuelta($this->tenant->id, $this->comprador->id, 'rejected', 'merchant', 'El producto llegó completo y sin daños.');

    $this->avisos->claimResolved($r->id);

    expect($this->comprador->notifications()->first()->data['body'])
        ->toContain('El producto llegó completo');
});

it('el KYC enviado avisa a la plataforma y no lleva la cédula', function () {
    /*
     * Un buzón se consulta muchas veces y casi siempre sin necesitar el dato sensible. La misma
     * regla que sigue `ListAdminClaimsUseCase`: la identidad viaja solo en el expediente.
     */
    $perfil = TenantKycProfile::create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $this->tenant->id,
        'legal_name' => 'María Pérez',
        'cedula' => 'V-12345678',
        'rif' => 'J-401234567',
        'phone' => '+58 412 1234567',
        'address' => 'Av. Principal',
        'status' => 'pending',
    ]);

    $this->avisos->kycSubmitted($perfil->id);

    $aviso = $this->admin->notifications()->first();

    expect($aviso->data['type'])->toBe('kyc.submitted');
    expect($aviso->data['body'])->toContain('Bazar Central');
    expect(json_encode($aviso->data))->not->toContain('12345678');
    expect(json_encode($aviso->data))->not->toContain('María Pérez');
});

it('el KYC rechazado le dice al comerciante qué corregir', function () {
    $perfil = TenantKycProfile::create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $this->tenant->id,
        'legal_name' => 'María Pérez',
        'cedula' => 'V-12345678',
        'rif' => 'J-401234567',
        'phone' => '+58 412 1234567',
        'address' => 'Av. Principal',
        'status' => 'rejected',
        'rejection_reason' => 'La foto de la cédula está ilegible.',
    ]);

    $this->avisos->kycReviewed($perfil->id);

    $aviso = $this->dueno->notifications()->first()->data;

    expect($aviso['title'])->toContain('no se pudo verificar');
    expect($aviso['body'])->toContain('ilegible');
    // Sin verificar no puede cobrar: hay que decírselo, no solo que falló.
    expect($aviso['body'])->toContain('no puedes cobrar');
});

function retiroDe(string $tenantId, string $estado, ?string $notas = null): CommissionSettlement
{
    return CommissionSettlement::create([
        'id' => (string) Str::uuid(),
        'settlement_number' => 'SET-'.strtoupper(Str::random(5)),
        'tenant_id' => $tenantId,
        'type' => 'payout',
        'gross_sales_amount' => 500.0,
        'commission_amount' => 40.0,
        'net_amount' => 460.0,
        'currency' => 'USD',
        'status' => $estado,
        'notes' => $notas,
    ]);
}

it('un retiro aprobado dice que se pagó, leyendo el estado real', function () {
    /*
     * `ApproveCentralPayoutRequestUseCase` deja el retiro en `settled`, no en `paid`. Con el
     * estado equivocado este aviso le diría «rechazado» a quien acaba de cobrar.
     */
    $retiro = retiroDe($this->tenant->id, 'settled');

    $this->avisos->payoutResolved($retiro->id);

    $aviso = $this->dueno->notifications()->first()->data;

    expect($aviso['title'])->toContain('pagado');
    expect($aviso['body'])->toContain('460.00 USD');
});

it('un retiro rechazado dice el motivo', function () {
    $retiro = retiroDe($this->tenant->id, 'cancelled', 'Los datos bancarios no coinciden con el titular.');

    $this->avisos->payoutResolved($retiro->id);

    $aviso = $this->dueno->notifications()->first()->data;

    expect($aviso['title'])->toContain('rechazado');
    expect($aviso['body'])->toContain('no coinciden con el titular');
});

it('el retiro solicitado avisa a la plataforma con el importe', function () {
    $retiro = retiroDe($this->tenant->id, 'pending');

    $this->avisos->payoutRequested($retiro->id);

    $aviso = $this->admin->notifications()->first()->data;

    expect($aviso['type'])->toBe('payout.requested');
    expect($aviso['body'])->toContain('Bazar Central');
    expect($aviso['body'])->toContain('460.00 USD');
});

function cambioDePlan(string $tenantId, string $estado, string $solicitanteId, ?string $motivo = null): TenantPlanChangeRequest
{
    // `requested_plan_id` y `requested_by_user_id` tienen clave foranea: sin filas reales
    // detras, el fixture no entra.
    $plan = SubscriptionPlan::create([
        'id' => (string) Str::uuid(),
        'name' => 'Plan Pro',
        'slug' => 'plan-pro-'.Str::random(4),
        'price_monthly' => 30.0,
        'commission_rate' => 8.0,
        'is_active' => true,
    ]);

    return TenantPlanChangeRequest::create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $tenantId,
        'requested_plan_id' => $plan->id,
        'billing_cycle' => 'monthly',
        'status' => $estado,
        'requested_by_user_id' => $solicitanteId,
        'rejection_reason' => $motivo,
    ]);
}

it('el cambio de plan resuelto cumple la promesa de «te avisaremos»', function () {
    // La pantalla responde «Solicitud enviada. Te avisaremos cuando la revisemos» desde el
    // 23/08/2026, y hasta ahora no había con qué.
    $solicitud = cambioDePlan($this->tenant->id, 'approved', $this->dueno->id);

    $this->avisos->planChangeResolved($solicitud->id);

    $aviso = $this->dueno->notifications()->first()->data;

    expect($aviso['type'])->toBe('plan.resolved');
    expect($aviso['title'])->toContain('se aprobó');
});

it('el cambio de plan rechazado lleva el motivo y no miente sobre el plan actual', function () {
    $solicitud = cambioDePlan($this->tenant->id, 'rejected', $this->dueno->id, 'El plan solicitado no admite tu volumen de ventas.');

    $this->avisos->planChangeResolved($solicitud->id);

    $aviso = $this->dueno->notifications()->first()->data;

    expect($aviso['body'])->toContain('sigue igual');
    expect($aviso['body'])->toContain('no admite tu volumen');
});

it('el cambio de plan solicitado avisa a la plataforma', function () {
    $solicitud = cambioDePlan($this->tenant->id, 'pending', $this->dueno->id);

    $this->avisos->planChangeRequested($solicitud->id);

    expect($this->admin->notifications()->first()->data['type'])->toBe('plan.requested');
});

it('sin superadministradores activos no revienta, solo no avisa', function () {
    $this->admin->update(['is_active' => false]);

    $this->avisos->planChangeRequested(cambioDePlan($this->tenant->id, 'pending', $this->dueno->id)->id);

    expect(Notification::count())->toBe(0);
});

it('todos los avisos del personal llevan el enlace resuelto a su destinatario', function () {
    /*
     * Varias rutas del backoffice llevan el uuid del usuario y `own_user` lo comprueba, así que
     * un enlace con el marcador sin sustituir daría un 403 al pulsarlo.
     */
    $this->avisos->payoutResolved(retiroDe($this->tenant->id, 'settled')->id);

    $url = $this->dueno->notifications()->first()->data['url'];

    expect($url)->not->toContain('{recipient}');
    expect($url)->toContain($this->dueno->id);
});
