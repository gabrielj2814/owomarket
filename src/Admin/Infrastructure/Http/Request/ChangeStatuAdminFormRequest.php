<?php


namespace Src\Admin\Infrastructure\Http\Request;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Src\Admin\Infrastructure\Http\Data\ChangeStatuAdminData;
use Src\Shared\Helper\ApiResponse;

class ChangeStatuAdminFormRequest extends FormRequest {

    public ChangeStatuAdminData $data;

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
            'id'       => 'required|uuid:4',
            'statu'    => 'required|boolean:strict',
        ];
    }

    public function messages(): array
    {
        return [
            'id.required'       => 'El campo email es obligatorio.',
            'id.uuid'           => 'El Id no tiene un formato valido con cumple con el formato UUID v4',

            'statu.required'    => 'El campo statu es obligatorio.',
            'statu.boolean'     => 'El campo statu solo acepta true o false',
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
        $this->data=ChangeStatuAdminData::from([
            'id'       => $this->id,
            'statu'    => $this->statu,
        ]);
    }

}

?>
