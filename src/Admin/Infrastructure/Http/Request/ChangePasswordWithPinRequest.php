<?php

namespace Src\Admin\Infrastructure\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordWithPinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pin' => ['required', 'string', 'digits:6'],
            'password' => ['required', 'string', 'min:8'],
            'password_confirmation' => ['required', 'string', 'same:password'],
        ];
    }

    public function messages(): array
    {
        return [
            'pin.required' => 'El PIN de seguridad es obligatorio.',
            'pin.digits' => 'El PIN debe tener exactamente 6 dígitos.',
            'password.required' => 'La nueva contraseña es obligatoria.',
            'password.min' => 'La nueva contraseña debe tener al menos 8 caracteres.',
            'password_confirmation.same' => 'La confirmación de la contraseña no coincide.',
        ];
    }
}
