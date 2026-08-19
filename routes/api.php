<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Src\User\Infrastructure\Eloquent\Models\User;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');
// Route::get('/msg', function () {
//     return response()->json([
//         'data' => User::all()
//     ]);
// });
// // Route::prefix("auth")->group(callback: base_path("app/Modules/Core/Auth/Routes/Api.php"));
// // rutas servicio auth

Route::prefix('auth')->group(callback: base_path('src/Authentication/Infrastructure/Http/Routes/api.php'));
Route::prefix('user')->group(callback: base_path('src/User/Infrastructure/Http/Routes/api.php'));
Route::prefix('tenant')->group(callback: base_path('src/Tenant/Infrastructure/Http/Routes/api.php'));
Route::prefix('central/customer')->group(callback: base_path('src/CentralCustomer/Infrastructure/Http/Routes/apiCentral.php'));
Route::prefix('central/monetization')->group(callback: base_path('src/Monetization/Infrastructure/Http/Routes/apiCentral.php'));
Route::prefix('central/marketplace')->group(callback: base_path('src/CentralMarketplace/Infrastructure/Http/Routes/apiCentral.php'));
Route::prefix('exchange-rate')->group(callback: base_path('src/ExchangeRate/Infrastructure/Http/Routes/api.php'));
Route::prefix('central/exchange-rate')->group(callback: base_path('src/ExchangeRate/Infrastructure/Http/Routes/apiCentral.php'));
