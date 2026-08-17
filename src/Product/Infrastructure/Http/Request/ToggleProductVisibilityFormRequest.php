<?php

declare(strict_types=1);

namespace Src\Product\Infrastructure\Http\Request;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Src\Shared\Helper\ApiResponse;

final class ToggleProductVisibilityFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_visible' => ['nullable', 'boolean'],
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            ApiResponse::error(
                message: 'Error de validación al cambiar la visibilidad del producto',
                code: 422,
                errors: $validator->errors()->toArray()
            )
        );
    }
}
