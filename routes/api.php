<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Route::prefix("auth")->group(callback: base_path("app/Modules/Core/Auth/Routes/Api.php"));
// rutas servicio auth
Route::prefix("auth")->group(callback: base_path("src/Authentication/Infrastructure/Http/Routes/api.php"));
Route::prefix("user")->group(callback: base_path("src/User/Infrastructure/Http/Routes/api.php"));
Route::prefix("tenant")->group(callback: base_path("src/Tenant/Infrastructure/Http/Routes/api.php"));
