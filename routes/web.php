<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

foreach (config('tenancy.central_domains') as $domain) {

    Route::domain($domain)->group(function () {
        Route::get('/', function () {
            $domain = request()->getHost();
            return Inertia::render('welcome',[
                'domain' => $domain,
            ]);
        })->name('home');

        Route::prefix("auth")->group(callback: base_path("src/Authentication/Infrastructure/Http/Routes/web.php"));

        Route::prefix("backoffice")->group(function (){
            Route::prefix("admin")->group(callback: base_path("src/Admin/Infrastructure/Http/Routes/web.php"));
        })->middleware('auth');

        Route::get("/login", function(Request $request) {
            return redirect("/");
        })->name("login");
    });

}

