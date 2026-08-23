<?php

declare(strict_types=1);

use Illuminate\Support\Facades\RateLimiter;

/*
|--------------------------------------------------------------------------
| Hallazgo N18 — limite de tasa en toda la aplicacion
|--------------------------------------------------------------------------
|
| Hasta la Fase 4.1 no habia ninguno: se podian probar contrasenas contra el login a
| ritmo de maquina, dar de alta cuentas en bucle o quemar tokens SSO sin freno.
|
| Este comentario decia que aquella fase "puso `throttle:5,15` en las dos rutas del PIN".
| No era cierto, y esa frase — repetida tambien en apiCentral.php — es probablemente la
| razon de que el hueco durara: dos sitios afirmaban que la puerta estaba cerrada. La
| cerro el hallazgo A2, y los tests del final la vigilan.
|
| Los tests golpean los endpoints reales, no el limitador aislado: lo que hay que
| demostrar es que la puerta esta cerrada, no que la cerradura funcione en el banco.
*/

beforeEach(function () {
    RateLimiter::clear('credenciales');
    RateLimiter::clear('recuperacion');
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

/*
|--------------------------------------------------------------------------
| Hallazgo A2 — las dos rutas del PIN del cliente
|--------------------------------------------------------------------------
|
| El PIN son seis digitos y vale 15 minutos. Medido contra la aplicacion real se probaban
| 42 seguidos sin ver un solo 429. No se agota el millon de combinaciones en una ventana
| —son unos 9 intentos por segundo, un 0,9 % del espacio— pero pedir un PIN nuevo tampoco
| tenia freno, asi que se encadenaban ventanas hasta cubrirlo entero. Un fin de semana
| contra una cuenta concreta.
|
| El mismo agujero se habia cerrado ya en el PIN del administrador (A7) y no se copio a
| este. De ahi que los dos tests de abajo golpeen el flujo del CLIENTE en concreto.
*/

test('adivinar el PIN se corta tras cinco intentos (A2)', function () {
    $intento = ['email' => 'victima.pin@example.com', 'pin_code' => '000000', 'password' => 'OwO_12345678'];

    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/central/customer/reset-password', $intento);
    }

    $this->postJson('/api/central/customer/reset-password', $intento)
        ->assertStatus(429)
        ->assertJsonPath('status', 'error');
});

test('pedir codigos de recuperacion se corta tras tres por hora (A2)', function () {
    // Sin esto, el limite de arriba no sirve de nada: se encadenan ventanas pidiendo un
    // PIN nuevo cada vez. Y ademas cada peticion es un correo a costa del proyecto.
    $peticion = ['email' => 'buzon.lleno@example.com'];

    for ($i = 0; $i < 3; $i++) {
        $this->postJson('/api/central/customer/forgot-password', $peticion);
    }

    $this->postJson('/api/central/customer/forgot-password', $peticion)
        ->assertStatus(429)
        ->assertJsonPath('status', 'error');
});

test('el limite de recuperacion no castiga a toda una conexion por una cuenta (A2)', function () {
    // Por eso no se reuso `throttle:altas`, que cuenta 3/hora solo por IP: detras de un
    // NAT de oficina, una persona pidiendo tres codigos dejaria a los demas sin la unica
    // puerta que les queda. El cerrojo por IP existe, pero mas alto (10/hora).
    for ($i = 0; $i < 3; $i++) {
        $this->postJson('/api/central/customer/forgot-password', ['email' => 'una.persona@example.com']);
    }

    $respuesta = $this->postJson('/api/central/customer/forgot-password', ['email' => 'su.companera@example.com']);

    expect($respuesta->status())->not->toBe(429);
});

test('un correo sin cuenta no se distingue de uno con cuenta (A3)', function () {
    // La otra mitad del ataque: A3 daba la lista de cuentas, A2 dejaba atacarlas. Aqui se
    // comprueba desde fuera, que es como lo vio quien lo reporto: mismo codigo, mismo texto.
    $conCuenta = 'existe_'.bin2hex(random_bytes(3)).'@example.com';

    $this->postJson('/api/central/customer/register', [
        'name' => 'Cliente A3',
        'email' => $conCuenta,
        'password' => 'OwO_12345678',
        'password_confirmation' => 'OwO_12345678',
    ]);

    $existe = $this->postJson('/api/central/customer/forgot-password', ['email' => $conCuenta]);
    $noExiste = $this->postJson('/api/central/customer/forgot-password', ['email' => 'no.existe.jamas@example.com']);

    $existe->assertStatus(200);
    $noExiste->assertStatus(200);
    expect($noExiste->json('message'))->toBe($existe->json('message'));
});
