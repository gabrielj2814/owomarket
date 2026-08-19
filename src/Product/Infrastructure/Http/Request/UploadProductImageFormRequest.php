<?php

declare(strict_types=1);

namespace Src\Product\Infrastructure\Http\Request;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Src\Shared\Helper\ApiResponse;

final class UploadProductImageFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'alt_text' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'El archivo de imagen es obligatorio.',
            'file.image' => 'El archivo debe ser una imagen válida.',
            'file.mimes' => 'Los formatos permitidos son: JPEG, PNG, JPG y WebP.',
            'file.max' => 'El tamaño máximo permitido para la imagen es de 5MB.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ApiResponse::error(
                message: 'Error de validación al subir la imagen',
                code: 422,
                errors: $validator->errors()->toArray()
            )
        );
    }
}
