<?php

declare(strict_types=1);

namespace Src\Coupon\Infrastructure\Http\Request;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Src\Shared\Helper\ApiResponse;

final class ValidateCouponFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string'],
            'order_subtotal' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'El código del cupón es obligatorio.',
            'order_subtotal.required' => 'El subtotal de la orden es obligatorio.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            ApiResponse::error(
                message: 'Error de validación al verificar el cupón',
                code: 422,
                errors: $validator->errors()->toArray()
            )
        );
    }
}
