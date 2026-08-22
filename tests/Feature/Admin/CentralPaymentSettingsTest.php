<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Src\Payment\Infrastructure\Eloquent\Models\CentralSetting;
use Src\User\Infrastructure\Eloquent\Models\User;

/**
 * Hallazgo N33: la Fase 3.4 dejó el checkout central leyendo `central_settings`, pero **no
 * había dónde escribirlos**: sólo los ponía un seeder de desarrollo o un `INSERT` a mano.
 *
 * Estos datos deciden a qué cuenta transfiere el comprador de un pedido multi-tienda, así
 * que la pantalla va bajo `super_admin`, no bajo un permiso de staff.
 */
beforeEach(function () {
    // La pagina es nueva y el manifiesto de Vite local puede no tenerla todavia; lo que se
    // prueba aqui es el controlador y los permisos, no el bundle.
    $this->withoutVite();

    $this->admin = User::create([
        'id' => (string) Str::uuid(),
        'name' => 'Super Admin',
        'email' => 'admin_'.bin2hex(random_bytes(4)).'@owomarket.com',
        'password' => bcrypt('Password123!'),
        'type' => 'super_admin',
        'is_active' => true,
    ]);
});

test('un usuario que no es super admin no puede ver la pantalla', function () {
    $owner = User::create([
        'id' => (string) Str::uuid(),
        'name' => 'Dueño de Tienda',
        'email' => 'owner_'.bin2hex(random_bytes(4)).'@owomarket.com',
        'password' => bcrypt('Password123!'),
        'type' => 'tenant_owner',
        'is_active' => true,
    ]);

    $this->actingAs($owner)
        ->get("http://owomarket.local/admin/backoffice/{$owner->id}/payment-settings")
        ->assertStatus(403);
});

test('el super admin ve la pantalla y, sin datos, ningún método activo', function () {
    $this->actingAs($this->admin)
        ->get("http://owomarket.local/admin/backoffice/{$this->admin->id}/payment-settings")
        ->assertStatus(200)
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/payments/CentralPaymentSettingsPage')
            ->has('active_methods', 0)
        );
});

test('guardar los datos hace que el método se ofrezca en el checkout central', function () {
    $this->actingAs($this->admin)
        ->putJson('http://owomarket.local/admin/backoffice/payment-settings', [
            'central_pago_movil_bank_name' => '0105 - Banco Mercantil',
            'central_pago_movil_document_id' => 'J-50999888-1',
            'central_pago_movil_phone' => '0424-5556677',
        ])
        ->assertStatus(200);

    expect(CentralSetting::where('key', 'central_pago_movil_phone')->value('value'))->toBe('0424-5556677');
    expect(CentralSetting::where('key', 'central_pago_movil_phone')->value('group'))->toBe('payment');

    // Y el checkout central pasa a ofrecerlo.
    $this->get('http://owomarket.local/checkout')
        ->assertInertia(fn (Assert $page) => $page
            ->has('payment_methods', 1)
            ->where('payment_methods.0.id', 'pago_movil')
        );
});

// La regla de la Fase 0.5 se mantiene: incompleto es lo mismo que no configurado.
test('unos datos incompletos no habilitan el método', function () {
    $this->actingAs($this->admin)
        ->putJson('http://owomarket.local/admin/backoffice/payment-settings', [
            'central_pago_movil_bank_name' => '0105 - Banco Mercantil',
            'central_pago_movil_document_id' => 'J-50999888-1',
            // falta el teléfono
        ])
        ->assertStatus(200);

    $this->get('http://owomarket.local/checkout')
        ->assertInertia(fn (Assert $page) => $page->has('payment_methods', 0));
});

test('vaciar un campo retira el método del checkout', function () {
    foreach ([
        'central_pago_movil_bank_name' => '0105 - Banco Mercantil',
        'central_pago_movil_document_id' => 'J-50999888-1',
        'central_pago_movil_phone' => '0424-5556677',
    ] as $key => $value) {
        CentralSetting::create([
            'id' => (string) Str::uuid(),
            'key' => $key,
            'value' => $value,
            'type' => 'string',
            'group' => 'payment',
        ]);
    }

    $this->actingAs($this->admin)
        ->putJson('http://owomarket.local/admin/backoffice/payment-settings', [
            'central_pago_movil_phone' => '',
        ])
        ->assertStatus(200);

    $this->get('http://owomarket.local/checkout')
        ->assertInertia(fn (Assert $page) => $page->has('payment_methods', 0));
});
