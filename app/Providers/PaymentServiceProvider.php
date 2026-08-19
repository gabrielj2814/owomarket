<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Src\Payment\Application\Contracts\PaymentGatewayFactoryInterface;
use Src\Payment\Infrastructure\Adapters\CashOnDeliveryPaymentGateway;
use Src\Payment\Infrastructure\Adapters\ManualBankTransferPaymentGateway;
use Src\Payment\Infrastructure\Factory\PaymentGatewayFactory;

class PaymentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(PaymentGatewayFactoryInterface::class, function ($app) {
            $factory = new PaymentGatewayFactory($app);

            // Registrar adaptadores de pago disponibles
            $factory->register('manual_transfer', ManualBankTransferPaymentGateway::class);
            $factory->register('manual', ManualBankTransferPaymentGateway::class);
            $factory->register('cash_on_delivery', CashOnDeliveryPaymentGateway::class);
            $factory->register('cash', CashOnDeliveryPaymentGateway::class);

            return $factory;
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void {}
}
