<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Sanctum\Sanctum;
use Src\Admin\Infrastructure\Services\AuthApiClient;
use Src\Authentication\Application\Contracts\AuthServices;
use Src\Authentication\Application\Contracts\UserServices;
use Src\Authentication\Infrastructure\Eloquent\Models\PersonalAccessToken;
use Src\Authentication\Infrastructure\Services\UserApiClient;
use Stancl\Tenancy\Events\TenancyBootstrapped;
use Stancl\Tenancy\Events\TenancyEnded;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(UserServices::class, UserApiClient::class);
        $this->app->bind(AuthServices::class, AuthApiClient::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        /*
        |----------------------------------------------------------------------
        | Que es una contrasena valida (hallazgo A4)
        |----------------------------------------------------------------------
        |
        | Habia cuatro respuestas distintas: el registro de cliente pedia min:6, el reset
        | min:8, el formulario de reset comprobaba >= 8 por su cuenta, y los dos logins
        | exigian en el navegador 8-72 con mayuscula, minuscula, digito y simbolo.
        |
        | La consecuencia practica: alguien se registraba con `abc123` —el servidor lo
        | aceptaba— y despues el sistema le exigia otra cosa. Y como las reglas duras solo
        | vivian en el navegador, el servidor no comprobaba complejidad EN NINGUN SITIO:
        | eran un obstaculo para el usuario honesto y ninguno para quien enviara la
        | peticion a mano.
        |
        | Esta es ahora la unica definicion. Registro y reset la usan via
        | Password::defaults(), asi que coinciden por construccion y no por disciplina.
        |
        | Solo se aplica a contrasenas NUEVAS. Quien ya tenia una de seis caracteres sigue
        | entrando: validar el formato de una contrasena que ya existe no protege nada y
        | solo deja fuera a gente con contrasenas antiguas. Por eso se quito esa
        | comprobacion de los dos formularios de login.
        */
        // El max(72) es el limite de bcrypt: por encima trunca en SILENCIO, asi que dos
        // contrasenas distintas que compartan los primeros 72 bytes abren la misma cuenta.
        // El alta de comerciante ya lo llevaba ('min:8|max:72') y el cierre de A4 lo perdio
        // al unificar: se cayo una parte que estaba bien. Aqui vuelve, y ahora para todos.
        Password::defaults(fn () => Password::min(8)->max(72)->mixedCase()->numbers()->symbols());

        /*
        |----------------------------------------------------------------------
        | URLs absolutas en el dominio de la tienda (tarea 1 de la auditoría)
        |----------------------------------------------------------------------
        |
        | Esto era un `if (tenancy()->initialized)` aquí mismo, y **nunca se cumplía**: la
        | tenancy se inicializa durante la petición, en el middleware, mucho después de que
        | arranquen los providers. Así que toda URL absoluta en un dominio de tienda
        | —enlaces de correo, redirecciones— salía con el `APP_URL` central.
        |
        | Se escucha el evento en su lugar, que es el momento en el que la tienda ya se
        | conoce. Importa más desde N17 y N25: los jobs de la cola inicializan tenancy fuera
        | de una petición, y un correo enviado desde ahí tiene que enlazar a su tienda.
        |
        | El esquema sale de `app.url` y no de la petición, porque en un worker no hay
        | petición de la que sacarlo.
        */
        $urlCentral = (string) config('app.url');

        Event::listen(TenancyBootstrapped::class, function (TenancyBootstrapped $event): void {
            $dominio = $event->tenancy->tenant->domains->first()?->domain;

            if ($dominio === null) {
                return;
            }

            $esquema = str_starts_with((string) config('app.url'), 'https') ? 'https' : 'http';

            URL::forceRootUrl($esquema.'://'.$dominio);
            config(['app.url' => $esquema.'://'.$dominio]);
        });

        // Al terminar la tenancy se vuelve al dominio central. Sin esto, un worker que
        // procesa un job de la tienda A y despues trabajo central seguiría generando
        // enlaces apuntando a A.
        Event::listen(TenancyEnded::class, function () use ($urlCentral): void {
            URL::forceRootUrl($urlCentral);
            config(['app.url' => $urlCentral]);
        });
    }
}
