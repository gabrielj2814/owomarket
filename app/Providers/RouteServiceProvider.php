<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Src\Shared\Helper\ApiResponse;

/**
 * Limitadores de tasa de la aplicación (hallazgo N18).
 *
 * Hasta la Fase 4.1 no había ninguno: se podía probar contraseñas contra el login a
 * ritmo de máquina, dar de alta cuentas en bucle o quemar tokens SSO sin freno. Esa fase
 * puso `throttle:5,15` en las dos rutas del PIN de recuperación, pero el resto de la
 * aplicación seguía abierto.
 *
 * **Por qué aquí y no con `->middleware('throttle:5,15')` en cada ruta.** Un límite
 * repartido por veinte archivos de rutas es un límite que nadie puede auditar: no hay
 * forma de responder «¿qué protege el login?» sin recorrerlos todos. Definidos con
 * nombre, la política se lee de una vez y las rutas sólo dicen a cuál se acogen.
 *
 * **Por qué las claves no son sólo la IP.** Detrás de un NAT corporativo o de un móvil,
 * cientos de personas comparten IP: limitar sólo por IP castiga a usuarios legítimos.
 * Los límites de credenciales usan (identificador + IP), que es lo que distingue un
 * ataque de fuerza bruta contra una cuenta concreta.
 */
class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->limitadorDeCredenciales();
        $this->limitadorDeAltas();
        $this->limitadorDeRecuperacion();
        $this->limitadorDeSso();
        $this->limitadorGeneralDeApi();
    }

    /**
     * Login de staff, de propietario de tienda y de cliente central.
     *
     * Cinco por minuto contra la misma cuenta desde la misma IP. Es holgado para quien
     * se equivoca al teclear y ridículo para quien prueba un diccionario.
     */
    private function limitadorDeCredenciales(): void
    {
        RateLimiter::for('credenciales', function (Request $request) {
            $identificador = (string) $request->input('email', '');

            return [
                Limit::perMinute(5)->by(mb_strtolower($identificador).'|'.$request->ip())
                    ->response(fn () => $this->demasiadas('Demasiados intentos de acceso. Espera un minuto antes de volver a probar.')),

                // Segundo cerrojo por IP: sin él, rotar el email esquivaría el límite de
                // arriba y se podría barrer una lista de cuentas desde un solo sitio.
                Limit::perMinute(20)->by($request->ip())
                    ->response(fn () => $this->demasiadas('Demasiados intentos de acceso desde esta conexión.')),
            ];
        });
    }

    /**
     * Alta de clientes. Tres por hora y por IP: crear cuentas es legítimo, crear cientos
     * es lo que llena la base de basura y quema el envío de correo.
     */
    private function limitadorDeAltas(): void
    {
        RateLimiter::for('altas', fn (Request $request) => Limit::perHour(3)->by($request->ip())
            ->response(fn () => $this->demasiadas('Has creado demasiadas cuentas desde esta conexión. Inténtalo más tarde.')));
    }

    /**
     * Recuperación de contraseña por PIN (`forgot-password`).
     *
     * Dos cerrojos, por el mismo motivo que en `credenciales`. Por cuenta, tres por hora:
     * pedir un código es legítimo, pedir cientos es llenarle el buzón a alguien a costa
     * del proyecto. Por IP el techo es más alto a propósito — recuperar la contraseña es
     * justo lo que hace mucha gente detrás de un mismo NAT, y castigarles a todos por uno
     * les deja sin la única puerta que les quedaba.
     *
     * El consumo del PIN (`reset-password`) no usa este limitador sino `credenciales`:
     * ahí lo que se está adivinando es un secreto de seis dígitos, y eso se cuenta por
     * minuto, no por hora.
     */
    private function limitadorDeRecuperacion(): void
    {
        RateLimiter::for('recuperacion', function (Request $request) {
            $identificador = mb_strtolower((string) $request->input('email', ''));

            return [
                Limit::perHour(3)->by('recuperacion|'.$identificador)
                    ->response(fn () => $this->demasiadas('Ya has pedido varios códigos de recuperación. Revisa tu correo antes de pedir otro.')),

                Limit::perHour(10)->by('recuperacion-ip|'.$request->ip())
                    ->response(fn () => $this->demasiadas('Demasiadas solicitudes de recuperación desde esta conexión.')),
            ];
        });
    }

    /**
     * Generación y consumo de tokens SSO. Un token SSO es una credencial de un solo uso;
     * pedirlos en bucle es o un error de bucle en el cliente o un intento de adivinarlos.
     */
    private function limitadorDeSso(): void
    {
        RateLimiter::for('sso', fn (Request $request) => Limit::perMinute(10)
            ->by($request->user()?->getAuthIdentifier() ?? $request->ip())
            ->response(fn () => $this->demasiadas('Demasiadas solicitudes de acceso entre dominios. Espera un momento.')));
    }

    /**
     * Techo general para las APIs (`/api/*` y `/api-tenant/*`).
     *
     * Deliberadamente alto: no es una defensa contra abuso dirigido —de eso se encargan
     * los limitadores de arriba— sino un tope que impide que un bucle roto en el
     * navegador o un raspador tumben la base de datos. Se cuenta por usuario cuando hay
     * sesión, para que varias personas tras la misma IP no compartan cupo.
     */
    private function limitadorGeneralDeApi(): void
    {
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)
            ->by($request->user()?->getAuthIdentifier() ?? $request->ip())
            ->response(fn () => $this->demasiadas('Estás haciendo demasiadas peticiones. Espera un momento.')));
    }

    /**
     * Respuesta 429 con el mismo sobre JSON que usa el resto de la aplicación, en vez de
     * la página HTML por defecto de Laravel: estos endpoints los consume el frontend con
     * axios, y un HTML donde espera JSON se convierte en un error incomprensible.
     */
    private function demasiadas(string $mensaje)
    {
        return ApiResponse::error(message: $mensaje, code: 429);
    }
}
