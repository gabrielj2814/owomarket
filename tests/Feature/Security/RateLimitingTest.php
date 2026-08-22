<?php

declare(strict_types=1);

use Illuminate\Support\Facades\RateLimiter;

/*
|--------------------------------------------------------------------------
| Hallazgo N18 — limite de tasa en toda la aplicacion
|--------------------------------------------------------------------------
|
| Hasta la Fase 4.1 no habia ninguno: se podian probar contrasenas contra el login a
| ritmo de maquina, dar de alta cuentas en bucle o quemar tokens SSO sin freno. Esa fase
| puso `throttle:5,15` en las dos rutas del PIN, pero el resto seguia abierto.
|
| Los tests golpean los endpoints reales, no el limitador aislado: lo que hay que
| demostrar es que la puerta esta cerrada, no que la cerradura funcione en el banco.
*/

beforeEach(function () {
    RateLimiter::clear('credenciales');
    cache()->flush();
});

test('el login del cliente central se corta tras cinco intentos (N18)', function () {
    $credenciales = ['email' => 'fuerza.bruta@example.com', 'password' => 'loQueSea_1234'];

    // Cinco fallos son plausibles tecleando mal; el sexto ya es una maquina.
    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/central/customer/login', $credenciales);
    }

    $this->postJson('/api/central/customer/login', $credenciales)
        ->assertStatus(429)
        ->assertJsonPath('status', 'error');
});

test('el limite del login distingue una cuenta de otra (N18)', function () {
    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/central/customer/login', [
            'email' => 'victima@example.com',
            'password' => 'loQueSea_1234',
        ]);
    }

    // Otra cuenta desde la misma IP sigue pudiendo entrar: la clave es (email + IP), y
    // limitar solo por IP castigaria a todo un NAT corporativo por culpa de uno.
    // Seis peticiones en total desde esta IP, muy por debajo del cerrojo de 20/min, asi
    // que lo unico que podria cortar aqui es el limite por cuenta — y esta es otra cuenta.
    $respuesta = $this->postJson('/api/central/customer/login', [
        'email' => 'otra.persona@example.com',
        'password' => 'loQueSea_1234',
    ]);

    expect($respuesta->status())->not->toBe(429);
});

test('el alta de clientes se corta tras tres cuentas por hora (N18)', function () {
    for ($i = 0; $i < 3; $i++) {
        $this->postJson('/api/central/customer/register', [
            'name' => "Cuenta {$i}",
            'email' => "granja{$i}@example.com",
            'password' => 'OwO_12345678',
            'password_confirmation' => 'OwO_12345678',
        ]);
    }

    $this->postJson('/api/central/customer/register', [
        'name' => 'Cuenta 4',
        'email' => 'granja4@example.com',
        'password' => 'OwO_12345678',
        'password_confirmation' => 'OwO_12345678',
    ])->assertStatus(429);
});

test('el 429 responde en JSON y no en HTML (N18)', function () {
    // El frontend consume estos endpoints con axios: un HTML donde espera JSON se
    // convierte en un error incomprensible en pantalla.
    for ($i = 0; $i < 6; $i++) {
        $respuesta = $this->postJson('/api/central/customer/login', [
            'email' => 'json@example.com',
            'password' => 'loQueSea_1234',
        ]);
    }

    $respuesta->assertStatus(429)
        ->assertHeader('content-type', 'application/json')
        ->assertJsonStructure(['status', 'code', 'message']);
});
