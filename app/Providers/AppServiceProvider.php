<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
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
