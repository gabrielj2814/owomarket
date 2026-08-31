<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant as ModelsTenant;
use Src\TenantSettings\Infrastructure\Eloquent\Models\TenantSetting;
use Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper;
use Stancl\Tenancy\Events\TenantCreated;
use Stancl\Tenancy\Events\TenantDeleted;

/**
 * Fase 0.5 — hallazgo G1.
 *
 * El checkout servía datos de cobro de demostración hardcodeados (RIF
 * 'J-50123456-0', Binance Pay ID '284759302', tasa 40,50 Bs/USD). El comprador
 * transfería a una cuenta que no era la de la tienda. Ahora los métodos de
 * pago se construyen desde la configuración real del comercio, y el que no
 * esté configurado no se ofrece.
 */
beforeEach(function () {
    Event::fake([TenantCreated::class, TenantDeleted::class]);

    config([
        'tenancy.bootstrappers' => array_values(array_filter(
            config('tenancy.bootstrappers', []),
            fn ($bootstrapper) => $bootstrapper !== DatabaseTenancyBootstrapper::class
        )),
    ]);

    if (! Schema::hasTable('categories')) {
        (require base_path('database/migrations/tenant/2025_10_28_142911_create_categories.php'))->up();
    }
    if (! Schema::hasTable('tenant_settings')) {
        (require base_path('database/migrations/tenant/2025_10_28_144914_create_tenant_settings.php'))->up();
    }

    $tenantId = 't_'.bin2hex(random_bytes(4));
    $this->tenant = ModelsTenant::create([
        'id' => $tenantId,
        'name' => 'Tienda Pagos',
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

afterEach(function () {
    if (tenancy()->initialized) {
        tenancy()->end();
    }
});

function seedPaymentSetting(string $key, string $value): void
{
    TenantSetting::create([
        'id' => (string) Str::uuid(),
        'key' => $key,
        'value' => $value,
        'type' => 'string',
        'group' => 'payment',
    ]);
}

/**
 * Desde el 30/08/2026 la plataforma cobra TODAS las ventas, tambien las del escaparate de
 * cada tienda. Los datos de cobro que ve el comprador salen de `central_settings`, no de los
 * ajustes del inquilino.
 *
 * Nombre propio y no `seedCentralPaymentSetting`: ese ya lo declara
 * `CentralCheckoutPaymentMethodsTest`, y las funciones de los ficheros de Pest son globales.
 */
function seedAjusteCentralDeCobro(string $key, string $value): void
{
    Src\Payment\Infrastructure\Eloquent\Models\CentralSetting::updateOrCreate(
        ['key' => $key],
        ['group' => 'payment', 'value' => $value]
    );
}

test('Una tienda sin datos de cobro configurados no ofrece ningún método de pago', function () {
    $response = $this->get("http://{$this->domain}/checkout");

    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('marketplace/checkout/TenantCheckoutPage')
        ->has('payment_methods', 0)
    );
});

test('El checkout no expone nunca los datos de demostración hardcodeados', function () {
    $response = $this->get("http://{$this->domain}/checkout");

    $response->assertStatus(200);

    // Los literales que veía el comprador antes de la Fase 0.5.
    $response->assertDontSee('J-50123456-0');
    $response->assertDontSee('284759302');
    $response->assertDontSee('api.qrserver.com');
    $response->assertDontSee('0412-1234567');
});

test('Pago Móvil sólo se ofrece con banco, RIF y teléfono de la PLATAFORMA', function () {
    // Con configuración central incompleta (falta el teléfono) no debe ofrecerse.
    seedAjusteCentralDeCobro('central_pago_movil_bank_name', '0105 - Banco Mercantil');
    seedAjusteCentralDeCobro('central_pago_movil_document_id', 'J-50999888-1');

    $this->get("http://{$this->domain}/checkout")
        ->assertInertia(fn (Assert $page) => $page->has('payment_methods', 0));

    seedAjusteCentralDeCobro('central_pago_movil_phone', '0412-5554433');

    $this->get("http://{$this->domain}/checkout")
        ->assertInertia(fn (Assert $page) => $page
            ->has('payment_methods', 1)
            ->where('payment_methods.0.id', 'pago_movil')
            ->where('payment_methods.0.bank_name', '0105 - Banco Mercantil')
            ->where('payment_methods.0.document_id', 'J-50999888-1')
            ->where('payment_methods.0.phone', '0412-5554433')
        );
});

test('los datos bancarios del comerciante NUNCA llegan al comprador', function () {
    // El test que importa de este cambio. La tienda tiene sus propios datos de cobro
    // configurados --los que usaba hasta hoy-- y el comprador no debe ver ni uno: ese dinero
    // iria a la cuenta equivocada sin que nada lo avisara.
    seedPaymentSetting('pago_movil_bank_name', '0134 - Banesco DEL COMERCIANTE');
    seedPaymentSetting('pago_movil_document_id', 'J-40111222-3');
    seedPaymentSetting('pago_movil_phone', '0414-9998877');
    seedPaymentSetting('binance_pay_id', '999888777');
    seedPaymentSetting('bank_transfer_instructions', 'Banesco del comerciante, cuenta 0134-...');

    seedAjusteCentralDeCobro('central_pago_movil_bank_name', '0105 - Banco Mercantil');
    seedAjusteCentralDeCobro('central_pago_movil_document_id', 'J-50999888-1');
    seedAjusteCentralDeCobro('central_pago_movil_phone', '0412-5554433');

    $respuesta = $this->get("http://{$this->domain}/checkout");

    $respuesta->assertDontSee('Banesco DEL COMERCIANTE')
        ->assertDontSee('J-40111222-3')
        ->assertDontSee('0414-9998877')
        ->assertDontSee('999888777')
        ->assertDontSee('Banesco del comerciante');

    // Y sí ve los de la plataforma.
    $respuesta->assertInertia(fn (Assert $page) => $page
        ->has('payment_methods', 1)
        ->where('payment_methods.0.bank_name', '0105 - Banco Mercantil')
    );
});

test('Binance Pay sólo se ofrece con el Pay ID de la plataforma configurado', function () {
    seedAjusteCentralDeCobro('central_binance_pay_id', '999888777');

    $this->get("http://{$this->domain}/checkout")
        ->assertInertia(fn (Assert $page) => $page
            ->has('payment_methods', 1)
            ->where('payment_methods.0.id', 'binance_pay')
            ->where('payment_methods.0.binance_pay_id', '999888777')
            // El QR no se inventa: sin uno propio configurado, va vacío.
            ->where('payment_methods.0.qr_code', null)
        );
});

test('El pago contra entrega requiere que el comercio lo habilite', function () {
    $this->get("http://{$this->domain}/checkout")
        ->assertInertia(fn (Assert $page) => $page->has('payment_methods', 0));

    seedPaymentSetting('cash_on_delivery_enabled', 'true');

    $this->get("http://{$this->domain}/checkout")
        ->assertInertia(fn (Assert $page) => $page
            ->has('payment_methods', 1)
            ->where('payment_methods.0.id', 'cash_on_delivery')
        );
});

/**
 * Guarda contra la deriva entre `TenantDemoDataSeeder` y este proveedor.
 *
 * Como un método mal configurado sencillamente no se ofrece —sin error ni aviso—, si
 * alguien renombra una clave en un sitio y no en el otro, el checkout de desarrollo se
 * queda en blanco y nada lo delata. Las claves de abajo son literalmente las que siembra
 * el seeder de demostración.
 */
test('Las claves que siembra el seeder de demostración habilitan los cuatro métodos', function () {
    // Los datos de cobro son ahora de la plataforma; de la tienda queda solo el interruptor
    // de contra entrega, que sigue siendo decision suya.
    foreach ([
        'central_pago_movil_bank_name' => '0102 - Banco de Venezuela',
        'central_pago_movil_document_id' => 'J-40123456-7',
        'central_pago_movil_phone' => '0414-1234567',
        'central_pago_movil_holder_name' => 'OwoMarket C.A.',
        'central_binance_pay_id' => '123456789',
        'central_bank_transfer_instructions' => 'Banco Mercantil, cuenta 0105-0000-00-0000000000.',
    ] as $key => $value) {
        seedAjusteCentralDeCobro($key, $value);
    }

    seedPaymentSetting('cash_on_delivery_enabled', '1');

    $this->get("http://{$this->domain}/checkout")
        ->assertInertia(fn (Assert $page) => $page
            ->has('payment_methods', 4)
            ->where('payment_methods.0.id', 'pago_movil')
            ->where('payment_methods.1.id', 'binance_pay')
            ->where('payment_methods.2.id', 'bank_transfer')
            ->where('payment_methods.3.id', 'cash_on_delivery')
        );
});
