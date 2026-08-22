<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Src\Coupon\Application\Contracts\CouponRepositoryInterface;
use Src\Coupon\Application\UseCase\ValidateCouponUseCase;
use Src\Coupon\Infrastructure\Eloquent\Repositories\CouponRepository;

final class CouponServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            CouponRepositoryInterface::class,
            CouponRepository::class
        );

        /*
         * Hallazgo Auditoria #4: la zona en la que se decide el dia de calendario del
         * cupon. Se inyecta desde aqui —y no con `config()` dentro del caso de uso— para
         * que el dominio y la aplicacion sigan siendo instanciables sin framework, que es
         * como los montan los tests unitarios.
         */
        $this->app->bind(
            ValidateCouponUseCase::class,
            fn ($app) => new ValidateCouponUseCase(
                $app->make(CouponRepositoryInterface::class),
                (string) config('app.business_timezone', 'America/Caracas')
            )
        );
    }

    public function boot(): void {}
}
