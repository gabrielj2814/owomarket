<?php

declare(strict_types=1);

namespace Src\Payment\Application\UseCases;

use Src\Payment\Application\Contracts\PaymentGatewayFactoryInterface;
use Src\Payment\Application\DTOs\PaymentGatewayInfoData;

final class ListAvailablePaymentGatewaysUseCase
{
    public function __construct(
        private readonly PaymentGatewayFactoryInterface $gatewayFactory
    ) {}

    /**
     * @return array<PaymentGatewayInfoData>
     */
    public function execute(): array
    {
        $registered = $this->gatewayFactory->getRegisteredGateways();
        $gatewaysInfo = [];

        foreach ($registered as $identifier => $gatewayClass) {
            $instance = $this->gatewayFactory->make($identifier);
            $gatewaysInfo[] = new PaymentGatewayInfoData(
                identifier: $instance->getIdentifier(),
                display_name: $instance->getDisplayName(),
                is_offline: $instance->isOffline()
            );
        }

        return $gatewaysInfo;
    }
}
