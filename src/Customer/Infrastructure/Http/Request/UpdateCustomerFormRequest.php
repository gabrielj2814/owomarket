<?php

declare(strict_types=1);

namespace Src\Customer\Infrastructure\Http\Request;

use Illuminate\Foundation\Http\FormRequest;
use Src\Customer\Application\DTOs\UpdateCustomerData;

final class UpdateCustomerFormRequest extends FormRequest
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
        ];
    }

    public function toDto(): UpdateCustomerData
    {
        return new UpdateCustomerData(
            name: (string) $this->input('name'),
            email: (string) $this->input('email'),
            phone: $this->input('phone'),
            birth_date: $this->input('birth_date'),
            gender: $this->input('gender'),
            is_active: $this->input('is_active') === null ? null : $this->boolean('is_active'),
            accepts_marketing: $this->input('accepts_marketing') === null ? null : $this->boolean('accepts_marketing'),
            metadata: $this->input('metadata')
        );
    }
}
