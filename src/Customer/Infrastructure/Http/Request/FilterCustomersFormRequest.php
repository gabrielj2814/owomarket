<?php

declare(strict_types=1);

namespace Src\Customer\Infrastructure\Http\Request;

use Illuminate\Foundation\Http\FormRequest;
use Src\Customer\Application\DTOs\FilterCustomersCriteria;

final class FilterCustomersFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'accepts_marketing' => ['nullable', 'boolean'],
            'gender' => ['nullable', 'string', 'in:male,female,other'],
            'sort_by' => ['nullable', 'string', 'in:name,email,created_at'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function toDto(): FilterCustomersCriteria
    {
        return new FilterCustomersCriteria(
            search: $this->input('search'),
            is_active: $this->input('is_active') === null ? null : $this->boolean('is_active'),
            accepts_marketing: $this->input('accepts_marketing') === null ? null : $this->boolean('accepts_marketing'),
            gender: $this->input('gender'),
            sort_by: (string) $this->input('sort_by', 'created_at'),
            sort_direction: (string) $this->input('sort_direction', 'desc'),
            page: (int) $this->input('page', 1),
            per_page: (int) $this->input('per_page', 15)
        );
    }
}
