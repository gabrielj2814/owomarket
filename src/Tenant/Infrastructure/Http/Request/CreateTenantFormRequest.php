<?php


namespace Src\Tenant\Infrastructure\Http\Request;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Src\Shared\Helper\ApiResponse;
use Src\Tenant\Infrastructure\Http\Data\CreateTenantData;

class CreateTenantFormRequest extends FormRequest {


    public CreateTenantData $data;

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
            'store_name'     => 'required|min:2|unique:Src\Tenant\Infrastructure\Eloquent\Models\Tenant,name',
        ];
    }

    public function messages(): array
    {
        return [

            'store_name.required' => 'El campo store_name es obligatorio.',
            'store_name.min'      => 'El campo store_name debe tener al menos 2 caracteres.',
            'store_name.unique'   => 'El nombre de la tienda ya está en uso.',

        ];
    }

    protected function failedValidation(Validator $validator):JsonResponse
    {
        $errors=$validator->errors();
        $response=ApiResponse::error("Error al validar los datos",422,$errors);
        throw new HttpResponseException($response);
    }

    protected function passedValidation()
    {
        $this->data=CreateTenantData::from([
            'store_name' => $this->store_name,
        ]);
    }



}


?>
