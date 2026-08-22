<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Application\UseCases;

use Exception;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CentralCustomer;

final class GetCentralCustomerProfileUseCase
{
    public function execute(string $customerId): CentralCustomer
    {
        $customer = CentralCustomer::with('addresses')->find($customerId);

        if (! $customer || ! $customer->is_active) {
            throw new Exception('Cliente no encontrado o inactivo.', 404);
        }

        return $customer;
    }
}
