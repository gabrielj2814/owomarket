<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\CentralCustomer\Infrastructure\Http\Controller\AddCustomerAddressPOSTController;
use Src\CentralCustomer\Infrastructure\Http\Controller\GenerateSsoTokenPOSTController;
use Src\CentralCustomer\Infrastructure\Http\Controller\GetCustomerProfileGETController;
use Src\CentralCustomer\Infrastructure\Http\Controller\LoginCentralCustomerPOSTController;
use Src\CentralCustomer\Infrastructure\Http\Controller\RegisterCentralCustomerPOSTController;

Route::post('/register', RegisterCentralCustomerPOSTController::class);
Route::post('/login', LoginCentralCustomerPOSTController::class);
Route::post('/sso/generate-token', GenerateSsoTokenPOSTController::class);
Route::get('/profile/{id}', GetCustomerProfileGETController::class);
Route::post('/profile/{id}/address', AddCustomerAddressPOSTController::class);
