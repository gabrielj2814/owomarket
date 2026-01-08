<?php


namespace Src\Tenant\Infrastructure\Http\Request;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Src\Shared\Helper\ApiResponse;
use Src\Tenant\Infrastructure\Http\Data\CreateTenantOwnerAccountData;

class CreateTenantOwnerAccountFormRequest extends FormRequest {


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
            'name'           => 'required|min:2',
            'email'          => 'required|email|unique:Src\Tenant\Infrastructure\Eloquent\Models\User,email',
            'phone'          => 'required|min:11|max:11',
            'password'       => 'required|min:8|max:72',
            'store_name'     => 'required|min:2|unique:Src\Tenant\Infrastructure\Eloquent\Models\Tenant,name',
            'tenant_name'    => 'required|min:2|max:253|unique:Src\Tenant\Infrastructure\Eloquent\Models\Domain,domain',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'     => 'El campo name es obligatorio.',
            'name.min'          => 'El campo tiene que tener minimo de 2 caracteres',

            'email.required'    => 'El campo email es obligatorio.',
            'email.email'       => 'El campo email debe ser una dirección de correo electrónico válida.',
            'email.unique'      => 'No se puede usar un correo que ya esta en uso.',

            'phone.required'     => 'El campo phone es obligatorio.',
            'phone.min'          => 'El campo tiene que tener minimo de 11 caracteres',
            'phone.max'          => 'El campo solo permite un  maximo de 11 caracteres',

            'password.required' => 'El campo password es obligatorio.',
            'password.min'      => 'El campo password debe tener al menos 8 caracteres.',
            'password.max'      => 'El campo password no debe exceder los 72 caracteres',

            'store_name.required' => 'El campo store_name es obligatorio.',
            'store_name.min'      => 'El campo store_name debe tener al menos 2 caracteres.',
            'store_name.unique'   => 'El nombre de la tienda ya está en uso.',

            'tenant_name.required' => 'El campo tenant_name es obligatorio.',
            'tenant_name.min'      => 'El campo tenant_name debe tener al menos 2 caracteres.',
            'tenant_name.max'      => 'El campo tenant_name no debe exceder los 253 caracteres',
            'tenant_name.unique'   => 'El nombre del tenant ya está en uso.',

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
        $this->data=CreateTenantOwnerAccountData::from([
            'name'     => $this->name,
            'email'    => $this->email,
            'phone'    => $this->phone,
            'password' => $this->password,
            'store_name' => $this->store_name,
            'tenant_name' => $this->tenant_name,
        ]);
    }



}



?>
