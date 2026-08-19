<?php

declare(strict_types=1);

namespace Src\Tax\Infrastructure\Http\Request;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Src\Shared\Helper\ApiResponse;

final class CreateTaxRateFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:150'],
            'rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'zip' => ['nullable', 'string', 'max:50'],
            'priority' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del impuesto es obligatorio.',
            'rate.required' => 'La tasa de impuesto es obligatoria.',
            'rate.min' => 'La tasa debe ser mayor o igual a 0.',
            'rate.max' => 'La tasa no puede ser mayor al 100%.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            ApiResponse::error(
                message: 'Error de validación al crear la tasa de impuesto',
                code: 422,
                errors: $validator->errors()->toArray()
            )
        );
    }
}
