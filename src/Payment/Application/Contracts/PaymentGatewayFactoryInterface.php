<?php

declare(strict_types=1);

namespace Src\Payment\Application\Contracts;

use Src\Payment\Domain\Contracts\PaymentGatewayInterface;

interface PaymentGatewayFactoryInterface
{
    /**
     * Registra una clase de pasarela vinculada a su identificador único.
     *
     * @param  class-string<PaymentGatewayInterface>  $gatewayClass
     */
    public function register(string $identifier, string $gatewayClass): void;

    /**
     * Resuelve e instancia la pasarela de pago solicitada vía IoC Container.
     *
     * @param  array<string, mixed>  $config
     */
    public function make(string $identifier, array $config = []): PaymentGatewayInterface;

    /**
     * Retorna todos los identificadores de pasarelas registradas.
     *
     * @return array<string, class-string<PaymentGatewayInterface>>
     */
    public function getRegisteredGateways(): array;
}
