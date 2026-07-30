<?php

namespace Src\Admin\Infrastructure\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

class UploadAdminAvatarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'avatar' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'avatar.required' => 'Debe seleccionar una imagen de perfil.',
            'avatar.image' => 'El archivo debe ser una imagen válida.',
            'avatar.mimes' => 'Formatos permitidos: JPEG, PNG, JPG, GIF, WEBP.',
            'avatar.max' => 'El tamaño máximo de la imagen es 2MB.',
        ];
    }
}
