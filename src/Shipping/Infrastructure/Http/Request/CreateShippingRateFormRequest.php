<?php

declare(strict_types=1);

namespace Src\Shipping\Infrastructure\Http\Request;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Src\Shared\Helper\ApiResponse;
use Src\Shipping\Domain\ValueObjects\ShippingRateType;

final class CreateShippingRateFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:150'],
            'type' => ['required', 'string', Rule::in(ShippingRateType::ALLOWED_TYPES)],
            'cost' => ['required', 'numeric', 'min:0'],
            'min_value' => ['nullable', 'numeric', 'min:0'],
            'max_value' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la tarifa es obligatorio.',
            'type.required' => 'El tipo de tarifa es obligatorio.',
            'type.in' => 'El tipo de tarifa no es válido.',
            'cost.required' => 'El costo de la tarifa es obligatorio.',
            'cost.min' => 'El costo no puede ser negativo.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            ApiResponse::error(
                message: 'Error de validación al crear la tarifa de envío',
                code: 422,
                errors: $validator->errors()->toArray()
            )
        );
    }
}
