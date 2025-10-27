<?php

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

    });
}

