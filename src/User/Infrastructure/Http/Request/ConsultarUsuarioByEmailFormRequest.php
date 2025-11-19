<?php


namespace Src\User\Infrastructure\Http\Request;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Src\Shared\Helper\ApiResponse;
use Src\User\Application\Data\EmailUserData;

class ConsultarUsuarioByEmailFormRequest extends FormRequest {


    public EmailUserData $data;

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
            'email'    => 'required|email',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'    => 'El campo email es obligatorio.',
            'email.email'       => 'El campo email debe ser una dirección de correo electrónico válida.',
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
        $this->data=EmailUserData::from([
            "email"       =>    $this->email,
        ]);
    }



}



?>
