<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Application\UseCases;

use App\Models\CentralCustomerAddress;
use Exception;

final class SetDefaultCentralCustomerAddressUseCase
{
    public function execute(string $customerId, string $addressId): CentralCustomerAddress
    {
        $address = CentralCustomerAddress::where('id', $addressId)
            ->where('customer_id', $customerId)
            ->first();

        if (! $address) {
            throw new Exception('Dirección no encontrada.', 404);
        }

        CentralCustomerAddress::where('customer_id', $customerId)
            ->update(['is_default' => false]);

        $address->is_default = true;
        $address->save();

        return $address;
    }
}
