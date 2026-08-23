<?php

namespace Src\Tenant\Infrastructure\Http\Request;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rules\Password;
use Src\Shared\Helper\ApiResponse;
use Src\Tenant\Infrastructure\Http\Data\CreateTenantOwnerAccountData;

class CreateTenantOwnerAccountFormRequest extends FormRequest
{
    public CreateTenantOwnerAccountData $data;

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
            'name' => 'required|string|min:3|max:100',
            'email' => 'required|email|max:150|unique:Src\Tenant\Infrastructure\Eloquent\Models\User,email',
            'phone' => 'required|string|min:8|max:25',
            // Hallazgo S2, cuarto hermano de A4. Era 'min:8|max:72' sin complejidad, asi
            // que el alta de COMERCIANTE —quien controla un catalogo, sus pedidos y sus
            // liquidaciones— aceptaba 'aaaaaaaa' mientras el registro de comprador ya
            // exigia mayuscula, minuscula, digito y simbolo. El max(72) no se pierde: vive
            // ahora dentro de Password::defaults().
            'password' => ['required', 'string', Password::defaults()],
            'confirmPassword' => 'nullable|same:password',
            'store_name' => 'required|string|min:3|max:100|unique:Src\Tenant\Infrastructure\Eloquent\Models\Tenant,name',
            'tenant_name' => 'required|string|min:2|max:253|unique:Src\Tenant\Infrastructure\Eloquent\Models\Domain,domain',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del titular es obligatorio.',
            'name.min' => 'El nombre debe tener al menos 3 caracteres.',
            'name.max' => 'El nombre no debe exceder los 100 caracteres.',

            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingresa un formato de correo electrónico válido.',
            'email.unique' => 'Este correo electrónico ya se encuentra registrado.',

            'phone.required' => 'El número telefónico de contacto es obligatorio.',
            'phone.min' => 'El número telefónico debe tener al menos 8 dígitos.',
            'phone.max' => 'El número telefónico no debe exceder 25 caracteres.',

            'password.required' => 'La contraseña es obligatoria.',
            // S2: sin mensajes propios para min/max. Decian «al menos 8 caracteres», que ya
            // no describe la regla, y un mensaje que miente sobre lo que se pide deja al
            // comerciante probando contrasenas a ciegas. El de Password::defaults() enumera
            // los requisitos que faltan.

            'confirmPassword.same' => 'Las contraseñas no coinciden.',

            'store_name.required' => 'El nombre de la tienda es obligatorio.',
            'store_name.min' => 'El nombre de la tienda debe tener al menos 3 caracteres.',
            'store_name.unique' => 'Ya existe una tienda registrada con este nombre.',

            'tenant_name.required' => 'El subdominio de la tienda es obligatorio.',
            'tenant_name.min' => 'El subdominio debe tener al menos 2 caracteres.',
            'tenant_name.unique' => 'Este subdominio ya está en uso por otro comercio.',
        ];
    }

    protected function failedValidation(Validator $validator): JsonResponse
    {
        $errors = $validator->errors();
        $response = ApiResponse::error('Error al validar los datos', 422, $errors);
        throw new HttpResponseException($response);
    }

    protected function passedValidation()
    {
        $this->data = CreateTenantOwnerAccountData::from([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'password' => $this->password,
            'store_name' => $this->store_name,
            'tenant_name' => $this->tenant_name,
        ]);
    }
}
