<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');


    // Route::get('dashboard/tenant', function () {
    //     return Inertia::render('dashboard/tenant');
    // })->name('dashboard.tenant');

    // Route::get('dashboard/admin', function () {
    //     return Inertia::render('dashboard/admin');
    // })->name('dashboard.admin');

require __DIR__.'/settings.php';
