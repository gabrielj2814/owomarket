<?php

declare(strict_types=1);

namespace Src\Attribute\Infrastructure\Http\Request;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Src\Shared\Helper\ApiResponse;

final class CreateAttributeValueFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'value' => ['required', 'string', 'min:1', 'max:150'],
            'color' => ['nullable', 'string', 'max:50'],
            'image' => ['nullable', 'string', 'max:500'],
            'position' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'value.required' => 'El valor del atributo es obligatorio.',
            'position.min' => 'La posición debe ser un número entero mayor o igual a 0.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            ApiResponse::error(
                message: 'Error de validación al crear el valor de atributo',
                code: 422,
                errors: $validator->errors()->toArray()
            )
        );
    }
}
