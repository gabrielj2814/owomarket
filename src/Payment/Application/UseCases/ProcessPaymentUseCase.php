<?php

declare(strict_types=1);

namespace Src\Payment\Application\UseCases;

use Src\Payment\Application\Contracts\PaymentGatewayFactoryInterface;
use Src\Payment\Application\DTOs\ProcessPaymentData;
use Src\Payment\Domain\ValueObjects\PaymentResult;

final class ProcessPaymentUseCase
{
    public function __construct(
        private readonly PaymentGatewayFactoryInterface $gatewayFactory
    ) {}

    public function execute(ProcessPaymentData $data): PaymentResult
    {
        $gateway = $this->gatewayFactory->make($data->payment_method);

        return $gateway->charge($data->toChargeArray());
    }
}
