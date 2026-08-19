<?php

declare(strict_types=1);

namespace Src\Category\Infrastructure\Http\Request;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Src\Shared\Helper\ApiResponse;

class EditCategoryFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id') ?? $this->id;

        return [
            'name' => 'required|string|min:2|max:150',
            'slug' => 'nullable|string|max:160|unique:categories,slug,'.$id,
            'description' => 'nullable|string',
            'image' => 'nullable|string',
            'parent_id' => 'nullable|integer|exists:categories,id',
            'is_active' => 'nullable|boolean',
            'position' => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la categoría es obligatorio.',
            'name.min' => 'El nombre debe tener al menos 2 caracteres.',
            'name.max' => 'El nombre no puede exceder los 150 caracteres.',
            'slug.unique' => 'El slug ya está en uso por otra categoría.',
            'parent_id.exists' => 'La categoría padre especificada no existe.',
        ];
    }

    protected function failedValidation(Validator $validator): JsonResponse
    {
        $errors = $validator->errors();
        $response = ApiResponse::error('Error al validar los datos de la categoría', 422, $errors);
        throw new HttpResponseException($response);
    }
}
