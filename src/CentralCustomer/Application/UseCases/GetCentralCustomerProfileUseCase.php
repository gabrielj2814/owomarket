<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Application\UseCases;

use App\Models\CentralCustomer;
use Exception;

final class GetCentralCustomerProfileUseCase
{
    /**
     * @param string $customerId
     * @return CentralCustomer
     */
    public function execute(string $customerId): CentralCustomer
    {
        $customer = CentralCustomer::with('addresses')->find($customerId);

        if (! $customer || ! $customer->is_active) {
            throw new Exception('Cliente no encontrado o inactivo.', 404);
        }

        return $customer;
    }
}
