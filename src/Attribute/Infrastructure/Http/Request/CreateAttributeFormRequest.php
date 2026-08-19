<?php

declare(strict_types=1);

namespace Src\Attribute\Infrastructure\Http\Request;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Src\Attribute\Domain\ValueObjects\AttributeType;
use Src\Shared\Helper\ApiResponse;

final class CreateAttributeFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:150'],
            'slug' => ['nullable', 'string', 'max:150', 'unique:product_attributes,slug'],
            'type' => ['nullable', 'string', Rule::in(AttributeType::ALLOWED_TYPES)],
            'is_filterable' => ['nullable', 'boolean'],
            'is_visible' => ['nullable', 'boolean'],
            'position' => ['nullable', 'integer', 'min:0'],
            'values' => ['nullable', 'array'],
            'values.*.value' => ['required_with:values', 'string', 'max:150'],
            'values.*.color' => ['nullable', 'string', 'max:50'],
            'values.*.image' => ['nullable', 'string', 'max:500'],
            'values.*.position' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del atributo es obligatorio.',
            'name.min' => 'El nombre debe tener al menos 2 caracteres.',
            'slug.unique' => 'El slug ya se encuentra registrado para otro atributo.',
            'type.in' => 'El tipo de atributo no es válido.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            ApiResponse::error(
                message: 'Error de validación al crear el atributo',
                code: 422,
                errors: $validator->errors()->toArray()
            )
        );
    }
}
