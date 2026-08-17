<?php

declare(strict_types=1);

namespace Src\Shipping\Infrastructure\Http\Request;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Src\Shared\Helper\ApiResponse;

final class CalculateShippingFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_value' => ['required', 'numeric', 'min:0'],
            'total_weight' => ['nullable', 'numeric', 'min:0'],
            'country' => ['nullable', 'string'],
            'state' => ['nullable', 'string'],
            'postal_code' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'order_value.required' => 'El valor de la orden es obligatorio.',
            'order_value.min' => 'El valor de la orden debe ser mayor o igual a 0.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            ApiResponse::error(
                message: 'Error de validación al calcular el envío',
                code: 422,
                errors: $validator->errors()->toArray()
            )
        );
    }
}
