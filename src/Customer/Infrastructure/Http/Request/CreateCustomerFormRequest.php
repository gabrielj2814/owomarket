<?php

declare(strict_types=1);

namespace Src\Customer\Infrastructure\Http\Request;

use Illuminate\Foundation\Http\FormRequest;
use Src\Customer\Application\DTOs\CreateCustomerData;
use Src\Customer\Application\DTOs\CustomerAddressInputData;

final class CreateCustomerFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'birth_date' => ['nullable', 'date_format:Y-m-d'],
            'gender' => ['nullable', 'string', 'in:male,female,other'],
            'is_active' => ['nullable', 'boolean'],
            'accepts_marketing' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
            'addresses' => ['nullable', 'array'],
            'addresses.*.first_name' => ['required_with:addresses', 'string', 'max:255'],
            'addresses.*.last_name' => ['required_with:addresses', 'string', 'max:255'],
            'addresses.*.address_line_1' => ['required_with:addresses', 'string', 'max:255'],
            'addresses.*.address_line_2' => ['nullable', 'string', 'max:255'],
            'addresses.*.city' => ['required_with:addresses', 'string', 'max:255'],
            'addresses.*.state' => ['required_with:addresses', 'string', 'max:255'],
            'addresses.*.postal_code' => ['required_with:addresses', 'string', 'max:50'],
            'addresses.*.country' => ['required_with:addresses', 'string', 'max:255'],
            'addresses.*.type' => ['nullable', 'string', 'in:shipping,billing,both,other'],
            'addresses.*.company' => ['nullable', 'string', 'max:255'],
            'addresses.*.phone' => ['nullable', 'string', 'max:50'],
            'addresses.*.is_default' => ['nullable', 'boolean'],
        ];
    }

    public function toDto(): CreateCustomerData
    {
        $addresses = [];
        if ($this->has('addresses') && is_array($this->input('addresses'))) {
            foreach ($this->input('addresses') as $addr) {
                $addresses[] = new CustomerAddressInputData(
                    first_name: (string) ($addr['first_name'] ?? ''),
                    last_name: (string) ($addr['last_name'] ?? ''),
                    address_line_1: (string) ($addr['address_line_1'] ?? ''),
                    city: (string) ($addr['city'] ?? ''),
                    state: (string) ($addr['state'] ?? ''),
                    postal_code: (string) ($addr['postal_code'] ?? ''),
                    country: (string) ($addr['country'] ?? ''),
                    type: (string) ($addr['type'] ?? 'shipping'),
                    address_line_2: $addr['address_line_2'] ?? null,
                    company: $addr['company'] ?? null,
                    phone: $addr['phone'] ?? null,
                    is_default: (bool) ($addr['is_default'] ?? false),
                    id: $addr['id'] ?? null
                );
            }
        }

        return new CreateCustomerData(
            name: (string) $this->input('name'),
            email: (string) $this->input('email'),
            phone: $this->input('phone'),
            birth_date: $this->input('birth_date'),
            gender: $this->input('gender'),
            is_active: $this->boolean('is_active', true),
            accepts_marketing: $this->boolean('accepts_marketing', false),
            metadata: $this->input('metadata'),
            addresses: $addresses
        );
    }
}
