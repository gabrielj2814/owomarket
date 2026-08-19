<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Application\UseCases;

use App\Models\CentralCustomerAddress;
use Exception;

final class UpdateCentralCustomerAddressUseCase
{
    /**
     * @param  array{label?: string, address?: string, city?: string, state?: string|null, zip_code?: string|null, country?: string, is_default?: bool}  $data
     */
    public function execute(string $customerId, string $addressId, array $data): CentralCustomerAddress
    {
        $address = CentralCustomerAddress::where('id', $addressId)
            ->where('customer_id', $customerId)
            ->first();

        if (! $address) {
            throw new Exception('Dirección no encontrada o no pertenece al usuario.', 404);
        }

        if (! empty($data['is_default'])) {
            CentralCustomerAddress::where('customer_id', $customerId)
                ->where('id', '!=', $addressId)
                ->update(['is_default' => false]);
            $address->is_default = true;
        } elseif (isset($data['is_default'])) {
            $address->is_default = (bool) $data['is_default'];
        }

        if (isset($data['label'])) {
            $address->label = trim($data['label']);
        }
        if (isset($data['address'])) {
            $address->address = trim($data['address']);
        }
        if (isset($data['city'])) {
            $address->city = trim($data['city']);
        }
        if (array_key_exists('state', $data)) {
            $address->state = $data['state'];
        }
        if (array_key_exists('zip_code', $data)) {
            $address->zip_code = $data['zip_code'];
        }
        if (isset($data['country'])) {
            $address->country = trim($data['country']);
        }

        $address->save();

        return $address;
    }
}
