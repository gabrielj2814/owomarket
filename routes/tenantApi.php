<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\User\Infrastructure\Eloquent\Models\User;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant API Routes
|--------------------------------------------------------------------------
*/

Route::prefix("auth")->group(callback: base_path("src/Authentication/Infrastructure/Http/Routes/apiTenant.php"));
Route::prefix("user")->group(callback: base_path("src/User/Infrastructure/Http/Routes/apiTenant.php"));
// Route::prefix("tenant")->group(callback: base_path("src/Tenant/Infrastructure/Http/Routes/api.php"));
