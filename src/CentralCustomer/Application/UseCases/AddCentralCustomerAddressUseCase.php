<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Application\UseCases;

use App\Models\CentralCustomer;
use App\Models\CentralCustomerAddress;
use Exception;
use Illuminate\Support\Str;

final class AddCentralCustomerAddressUseCase
{
    /**
     * @param string $customerId
     * @param array{label?: string, address: string, city: string, state?: string|null, zip_code?: string|null, country?: string, is_default?: bool} $data
     * @return CentralCustomerAddress
     */
    public function execute(string $customerId, array $data): CentralCustomerAddress
    {
        $customer = CentralCustomer::find($customerId);
        if (! $customer) {
            throw new Exception('Cliente no encontrado.', 404);
        }

        $isDefault = (bool) ($data['is_default'] ?? false);

        if ($isDefault) {
            CentralCustomerAddress::where('customer_id', $customerId)
                ->update(['is_default' => false]);
        }

        return CentralCustomerAddress::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $customer->id,
            'label' => $data['label'] ?? 'Principal',
            'address' => trim($data['address']),
            'city' => trim($data['city']),
            'state' => isset($data['state']) ? trim($data['state']) : null,
            'zip_code' => isset($data['zip_code']) ? trim($data['zip_code']) : null,
            'country' => $data['country'] ?? 'VE',
            'is_default' => $isDefault,
        ]);
    }
}
