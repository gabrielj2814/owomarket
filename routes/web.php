<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

foreach (config('tenancy.central_domains') as $domain) {

    Route::domain($domain)->group(function () {
        // Route::get('/', function () {
        //     $domain = request()->getHost();
        //     return Inertia::render('welcome',[
        //         'domain' => $domain,
        //     ]);
        // })->name('home');

        require base_path('src/Marketplace/Infrastructure/Http/Routes/web.php');
        require base_path('src/CentralCustomer/Infrastructure/Http/Routes/webCentral.php');
        require base_path('src/SupportTicket/Infrastructure/Http/Routes/web.php');

        Route::prefix('auth')->group(callback: base_path('src/Authentication/Infrastructure/Http/Routes/web.php'));

        Route::prefix('admin')->group(callback: base_path('src/Admin/Infrastructure/Http/Routes/web.php'));
        Route::prefix('admin')->group(callback: base_path('src/ExchangeRate/Infrastructure/Http/Routes/web.php'));
        // Hallazgo N33: datos de cobro de la plataforma, bajo `super_admin`.
        Route::prefix('admin')->group(callback: base_path('src/Payment/Infrastructure/Http/Routes/web.php'));
        Route::prefix('tenant')->group(callback: base_path('src/Tenant/Infrastructure/Http/Routes/web.php'));

        /*
        | El buzon del PERSONAL: administradores y comerciantes a la vez.
        |
        | Va aqui y no dentro de `admin/` o de `tenant/` porque los dos son filas de la misma
        | tabla `users` y comparten el guard `auth`. Montarlo dos veces daria dos URLs para el
        | mismo buzon, y el dia que una cambie la otra se queda atras.
        |
        | El comprador tiene el suyo bajo su propio guard, en
        | `src/CentralCustomer/Infrastructure/Http/Routes/apiCentral.php`.
        */
        Route::middleware('auth')->prefix('api')
            ->group(callback: base_path('src/Notification/Infrastructure/Http/Routes/shared.php'));

        Route::get('/login', function (Request $request) {
            return redirect('/auth/login');
        })->name('login');
    });

}
