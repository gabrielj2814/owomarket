<?php

declare(strict_types=1);

namespace Src\Payment\Infrastructure\Factory;

use Illuminate\Contracts\Container\Container;
use Src\Payment\Application\Contracts\PaymentGatewayFactoryInterface;
use Src\Payment\Domain\Contracts\PaymentGatewayInterface;
use Src\Payment\Domain\Exceptions\PaymentGatewayNotFoundException;

final class PaymentGatewayFactory implements PaymentGatewayFactoryInterface
{
    /** @var array<string, class-string<PaymentGatewayInterface>> */
    private array $gateways = [];

    public function __construct(
        private readonly Container $container
    ) {}

    public function register(string $identifier, string $gatewayClass): void
    {
        $this->gateways[strtolower(trim($identifier))] = $gatewayClass;
    }

    public function make(string $identifier, array $config = []): PaymentGatewayInterface
    {
        $key = strtolower(trim($identifier));

        if (! isset($this->gateways[$key])) {
            throw PaymentGatewayNotFoundException::withIdentifier($identifier);
        }

        return $this->container->make($this->gateways[$key], ['config' => $config]);
    }

    public function getRegisteredGateways(): array
    {
        return $this->gateways;
    }
}
