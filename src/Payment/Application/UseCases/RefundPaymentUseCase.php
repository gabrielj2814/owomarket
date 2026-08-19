<?php

declare(strict_types=1);

namespace Src\Payment\Application\UseCases;

use Src\Payment\Application\Contracts\PaymentGatewayFactoryInterface;
use Src\Payment\Application\DTOs\RefundPaymentData;
use Src\Payment\Domain\ValueObjects\RefundResult;

final class RefundPaymentUseCase
{
    public function __construct(
        private readonly PaymentGatewayFactoryInterface $gatewayFactory
    ) {}

    public function execute(RefundPaymentData $data): RefundResult
    {
        $gateway = $this->gatewayFactory->make($data->payment_method);

        return $gateway->refund($data->transaction_id, $data->amount, $data->reason);
    }
}
