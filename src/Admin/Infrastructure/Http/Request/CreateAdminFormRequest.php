<?php


namespace Src\Admin\Infrastructure\Http\Request;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Src\Admin\Infrastructure\Http\Data\CreateAdminData;
use Src\Admin\Infrastructure\Http\Data\UpdateAdminData;
use Src\Shared\Helper\ApiResponse;

class CreateAdminFormRequest extends FormRequest {

    public CreateAdminData $data;

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
            'name'     => 'required|min:2',
            'email'    => 'required|email',
            'phone'    => 'required|min:11|max:11',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'     => 'El campo email es obligatorio.',
            'name.min'          => 'El campo tiene que tener minimo de 2 caracteres',

            'email.required'    => 'El campo email es obligatorio.',
            'email.email'       => 'El campo email debe ser una dirección de correo electrónico válida.',

            'phone.required'     => 'El campo email es obligatorio.',
            'phone.min'          => 'El campo tiene que tener minimo de 11 caracteres',
            'phone.max'          => 'El campo solo permite un  maximo de 11 caracteres',
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
        $this->data=CreateAdminData::from([
            'name'     => $this->name,
            'email'    => $this->email,
            'phone'    => $this->phone,
        ]);
    }

}

?>
