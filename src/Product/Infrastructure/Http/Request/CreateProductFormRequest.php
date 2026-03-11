<?php


namespace Src\Product\Infrastructure\Http\Request;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Src\Product\Infrastructure\Http\Data\CreateProductData;
use Src\Shared\Helper\ApiResponse;

class CreateProductFormRequest extends FormRequest {


    public CreateProductData $data;

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
            'name'     => 'required|min:3|max:255',
            'slug'     => 'required|unique:products,slug',
            'price'    => 'required|numeric|min:0',
            'sku'      => 'required|unique:products,sku',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El campo name es obligatorio.',
            'name.min'      => 'El campo name debe tener al menos 3 caracteres.',
            'name.max'      => 'El campo name no debe exceder los 255 caracteres.',
            'slug.required' => 'El campo slug es obligatorio.',
            'slug.unique'   => 'El slug ya está en uso.',
            'price.required'=> 'El campo price es obligatorio.',
            'price.numeric' => 'El campo price debe ser un número.',
            'price.min'     => 'El campo price no puede ser negativo.',
            'sku.required'  => 'El campo sku es obligatorio.',
            'sku.unique'    => 'El SKU ya está en uso.',

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
        $this->data=CreateProductData::from([
            'name' => $this->name,
            'slug' => $this->slug,
            'price' => $this->price,
            'sku' => $this->sku,
        ]);
    }

}


?>
