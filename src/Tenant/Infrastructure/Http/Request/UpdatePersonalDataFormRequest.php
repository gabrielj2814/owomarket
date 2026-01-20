<?php

namespace Src\Tenant\Infrastructure\Http\Request;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Src\Shared\Helper\ApiResponse;
use Src\Tenant\Infrastructure\Http\Data\UpdatePersonalDataData;

class UpdatePersonalDataFormRequest extends FormRequest {


    public UpdatePersonalDataData $data;

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
            'phone'          => 'required|min:11|max:11',
        ];
    }

        public function messages(): array
    {
        return [
            'name.required'     => 'El campo name es obligatorio.',
            'name.min'          => 'El campo tiene que tener minimo de 2 caracteres',

            'phone.required'     => 'El campo phone es obligatorio.',
            'phone.min'          => 'El campo tiene que tener minimo de 11 caracteres',
            'phone.max'          => 'El campo solo permite un  maximo de 11 caracteres',

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
        $this->data=UpdatePersonalDataData::from([
            'name'     => $this->name,
            'phone'    => $this->phone,
        ]);
    }



}




?>
