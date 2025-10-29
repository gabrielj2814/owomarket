<?php

namespace App\Modules\Core\Auth\Request;

use App\Modules\Core\Auth\Data\AurhCredencialesData;
use App\Modules\Core\Helpers\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class LoginFormRequest extends FormRequest
{

    public AurhCredencialesData $data;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            //
            // 'email'    => 'required|email|unique:App\Models\User,email',
            'email'    => 'required|email',
            'password' => 'required|string|min:6',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'    => 'El campo email es obligatorio.',
            'email.email'       => 'El campo email debe ser una dirección de correo electrónico válida.',
            'password.required' => 'El campo contraseña es obligatorio.',
            'password.string'   => 'El campo contraseña debe ser una cadena de texto.',
            'password.min'      => 'El campo contraseña debe tener al menos :min caracteres.',
        ];
    }

    protected function failedValidation(Validator $validator):JsonResponse
    {
        $errors=$validator->errors();
        $response=ApiResponse::error("Error",422,$errors);
        throw new HttpResponseException($response);
    }

    protected function passedValidation()
    {
        $this->data=AurhCredencialesData::from([
            "email"       =>    $this->email,
            "password"    =>    $this->password,
        ]);
    }
}
