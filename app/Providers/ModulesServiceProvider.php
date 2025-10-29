<?php

namespace App\Providers;

use App\Modules\Core\Auth\Contracts\Auth;
use App\Modules\Core\Auth\Controller\AuthController;
use App\Modules\Core\Auth\Services\AuthServices;
use Illuminate\Support\ServiceProvider;

class ModulesServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //

        $this->app->when(AuthController::class)
            ->needs(Auth::class)
            ->give(AuthServices::class);

    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
