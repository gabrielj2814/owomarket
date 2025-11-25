<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Src\Authentication\Infrastructure\Http\Controller\AuthController;

foreach (config('tenancy.central_domains') as $domain) {

    Route::domain($domain)->group(function () {
        Route::get('/', function () {
            $domain = request()->getHost();
            return Inertia::render('welcome',[
                'domain' => $domain,
            ]);
        })->name('home');

        Route::prefix("auth")->group(callback: base_path("src/Authentication/Infrastructure/Http/Routes/web.php"));
    });

}

