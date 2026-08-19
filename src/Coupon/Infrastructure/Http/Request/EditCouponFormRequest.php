<?php

declare(strict_types=1);

namespace Src\Coupon\Infrastructure\Http\Request;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Src\Coupon\Domain\ValueObjects\CouponType;
use Src\Shared\Helper\ApiResponse;

final class EditCouponFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'code' => ['required', 'string', 'min:2', 'max:50', Rule::unique('coupons', 'code')->ignore($id)],
            'type' => ['required', 'string', Rule::in(CouponType::ALLOWED_TYPES)],
            'value' => ['required', 'numeric', 'gt:0'],
            'valid_from' => ['required', 'date_format:Y-m-d'],
            'valid_to' => ['required', 'date_format:Y-m-d', 'after_or_equal:valid_from'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'usage_limit_per_customer' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
            'applicable_categories' => ['nullable', 'array'],
            'applicable_products' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'El código del cupón es obligatorio.',
            'code.unique' => 'El código de cupón ya existe.',
            'type.required' => 'El tipo de cupón es obligatorio.',
            'type.in' => 'El tipo de cupón debe ser percentage o fixed_amount.',
            'value.required' => 'El valor de descuento es obligatorio.',
            'value.gt' => 'El valor de descuento debe ser mayor a 0.',
            'valid_from.required' => 'La fecha de inicio es obligatoria.',
            'valid_to.required' => 'La fecha de expiración es obligatoria.',
            'valid_to.after_or_equal' => 'La fecha de expiración debe ser posterior o igual a la fecha de inicio.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            ApiResponse::error(
                message: 'Error de validación al editar el cupón',
                code: 422,
                errors: $validator->errors()->toArray()
            )
        );
    }
}
