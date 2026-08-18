<?php

declare(strict_types=1);

namespace Src\Customer\Infrastructure\Http\Request;

use Illuminate\Foundation\Http\FormRequest;
use Src\Customer\Application\DTOs\CustomerAddressInputData;

final class AddCustomerAddressFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'address_line_1' => ['required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:50'],
            'country' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'in:shipping,billing,both,other'],
            'company' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }

    public function toDto(): CustomerAddressInputData
    {
        return new CustomerAddressInputData(
            first_name: (string) $this->input('first_name'),
            last_name: (string) $this->input('last_name'),
            address_line_1: (string) $this->input('address_line_1'),
            city: (string) $this->input('city'),
            state: (string) $this->input('state'),
            postal_code: (string) $this->input('postal_code'),
            country: (string) $this->input('country'),
            type: (string) $this->input('type', 'shipping'),
            address_line_2: $this->input('address_line_2'),
            company: $this->input('company'),
            phone: $this->input('phone'),
            is_default: $this->boolean('is_default', false),
            id: $this->input('id')
        );
    }
}
