<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Src\CentralCustomer\Application\Service\ClaimWindow;
use Src\Monetization\Application\UseCases\ReleaseOrderCommissionUseCase;
use Src\Payment\Application\UseCase\UpdateCentralPaymentSettingsUseCase;
use Src\Payment\Infrastructure\Eloquent\Models\CentralSetting;
use Src\Tenant\Infrastructure\Eloquent\Models\User;
use Stancl\Tenancy\Events\TenantCreated;
use Stancl\Tenancy\Events\TenantDeleted;

/**
 * Las reglas de garantía, guardadas desde una pantalla en vez de a mano en la base de datos.
 *
 * Los ocho ajustes ya estaban en la lista blanca del caso de uso y **ninguno tenía campo en
 * ninguna pantalla**. Peor: el controlador solo validaba los cinco datos bancarios, así que
 * `$request->only(KEYS)` dejaba pasar cualquier cosa en los otros.
 *
 * Lo que se vigila aquí es justo eso: que los valores imposibles se rechacen, y que los dos
 * ajustes que faltaban de verdad --la ventana de reclamación y el techo-- lleguen a guardarse.
 */
beforeEach(function () {
    Event::fake([TenantCreated::class, TenantDeleted::class]);

    $this->admin = User::create([
        'id' => (string) Str::uuid(),
        'name' => 'Super Admin',
        'email' => 'admin_'.Str::random(6).'@owomarket.com',
        'password' => bcrypt('password123'),
        'type' => 'super_admin',
        'is_active' => true,
    ]);
    $this->url = '/admin/backoffice/payment-settings';
});

it('guarda la ventana de reclamación, que hasta ahora no se podía escribir', function () {
    /*
     * `ClaimWindow` leía `central_claim_window_days` desde que existe, pero la clave no estaba
     * en la lista blanca: el número se podía leer y no se podía cambiar salvo tocando la base
     * de datos.
     */
    $this->actingAs($this->admin)
        ->putJson($this->url, ['central_claim_window_days' => '21'])
        ->assertOk();

    expect(UpdateCentralPaymentSettingsUseCase::current()['central_claim_window_days'])->toBe('21');
    expect((new ClaimWindow)->days())->toBe(21);
});

it('guarda el techo mensual de alarma', function () {
    $this->actingAs($this->admin)
        ->putJson($this->url, ['central_claim_monthly_alarm_usd' => '3500'])
        ->assertOk();

    expect(CentralSetting::where('key', 'central_claim_monthly_alarm_usd')->value('value'))->toBe('3500');
});

it('rechaza un porcentaje de reserva imposible', function () {
    // Un 150% de retención congelaría el dinero de todas las tiendas. Nadie lo escribe
    // queriendo, y hasta ahora se guardaba sin protestar.
    $this->actingAs($this->admin)
        ->putJson($this->url, ['central_guarantee_reserve_percent' => '150'])
        ->assertStatus(422);

    expect(CentralSetting::where('key', 'central_guarantee_reserve_percent')->exists())->toBeFalse();
});

it('rechaza texto donde va un número de días', function () {
    $this->actingAs($this->admin)
        ->putJson($this->url, ['central_claim_response_days' => 'abc'])
        ->assertStatus(422);
});

it('rechaza una ventana de reclamación de cero días', function () {
    // Cero cerraría de golpe las reclamaciones de todos los pedidos entregados. No es una
    // decisión que deba poder tomarse por accidente al escribir en una caja de texto.
    $this->actingAs($this->admin)
        ->putJson($this->url, ['central_claim_window_days' => '0'])
        ->assertStatus(422);
});

it('un envío parcial no borra los ajustes que no viajan', function () {
    /*
     * La pantalla de Reglas de garantía manda sus ocho claves y la de datos de cobro manda las
     * suyas, al MISMO endpoint. Si un envío parcial vaciara lo que no lleva, guardar las reglas
     * dejaría a la plataforma sin datos de cobro --y el checkout central sin métodos de pago--.
     */
    CentralSetting::create([
        'id' => (string) Str::uuid(),
        'group' => 'payment',
        'key' => 'central_pago_movil_bank_name',
        'value' => '0105 - Banco Mercantil',
    ]);

    $this->actingAs($this->admin)
        ->putJson($this->url, ['central_claim_window_days' => '14'])
        ->assertOk();

    expect(CentralSetting::where('key', 'central_pago_movil_bank_name')->value('value'))
        ->toBe('0105 - Banco Mercantil');
});

it('vaciar un campo devuelve el ajuste a su valor por defecto', function () {
    /*
     * La ruta que la pantalla usa para «quitar» un ajuste: no hay botón de borrar, se vacía la
     * caja y se guarda. El caso de uso guarda NULL, no cadena vacía, y ahí está lo importante:
     * `ReleaseOrderCommissionUseCase::porcentajeDeReserva()` comprueba `!== null`, así que una
     * cadena vacía guardada valdría **0%** y apagaría el fondo de garantía de todas las tiendas
     * en silencio.
     */
    $this->actingAs($this->admin)
        ->putJson($this->url, ['central_guarantee_reserve_percent' => '15'])
        ->assertOk();

    expect(CentralSetting::where('key', 'central_guarantee_reserve_percent')->value('value'))->toBe('15');

    $this->actingAs($this->admin)
        ->putJson($this->url, ['central_guarantee_reserve_percent' => ''])
        ->assertOk();

    expect(CentralSetting::where('key', 'central_guarantee_reserve_percent')->value('value'))->toBeNull();
});

it('un ajuste vaciado no deja el plazo en cero', function () {
    // Mismo peligro en el otro sentido: `ClaimWindow` cae a su defecto solo si la cadena vacía
    // se guarda como NULL. Si se guardara como '', `(int) ''` sería 0.
    $this->actingAs($this->admin)
        ->putJson($this->url, ['central_claim_window_days' => '21'])
        ->assertOk();
    expect((new ClaimWindow)->days())->toBe(21);

    $this->actingAs($this->admin)
        ->putJson($this->url, ['central_claim_window_days' => ''])
        ->assertOk();

    expect((new ClaimWindow)->days())->toBe(ClaimWindow::DIAS_POR_DEFECTO);
});

it('los valores por defecto son los que acordamos', function () {
    // Catorce dias para reclamar y treinta de reserva. La reserva tiene que cubrir el plazo de
    // reclamar mas el de respuesta de la tienda; mas alla de eso es dinero del comerciante
    // retenido cuando reclamar ya era imposible.
    expect(ClaimWindow::DIAS_POR_DEFECTO)->toBe(14);
    expect(ReleaseOrderCommissionUseCase::DIAS_POR_DEFECTO)->toBe(30);
    expect(ReleaseOrderCommissionUseCase::DIAS_POR_DEFECTO)
        ->toBeGreaterThan(ClaimWindow::DIAS_POR_DEFECTO);
});
