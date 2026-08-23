<?php

declare(strict_types=1);

namespace Src\Product\Infrastructure\Http\Request;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Src\Shared\Helper\ApiResponse;

final class UpdateProductStockFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:0'],
            // Hallazgo PR2: en un producto con variantes el stock vive en la variante, no
            // en el padre. Sin esto, reponer no surtia efecto y nadie se enteraba.
            'variant_id' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'quantity.required' => 'La cantidad en stock es obligatoria.',
            'quantity.min' => 'La cantidad en stock no puede ser negativa.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            ApiResponse::error(
                message: 'Error de validación al actualizar el stock',
                code: 422,
                errors: $validator->errors()->toArray()
            )
        );
    }
}
