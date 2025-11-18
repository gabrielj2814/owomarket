<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Src\Authentication\Application\Contracts\UserServices;
use Src\Authentication\Infrastructure\Services\UserApiClient;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //

        $this->app->bind(UserServices::class,UserApiClient::class);


    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
        if (tenancy()->initialized) {
            $currentDomain = tenancy()->tenant->domains->first()->domain;
            \URL::forceRootUrl('http://' . $currentDomain);

            // Para Vite/Webpack
            config(['app.url' => 'http://' . $currentDomain]);
        }
    }
}
