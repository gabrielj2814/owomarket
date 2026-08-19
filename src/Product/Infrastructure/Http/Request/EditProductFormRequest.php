<?php

declare(strict_types=1);

namespace Src\Product\Infrastructure\Http\Request;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Src\Shared\Helper\ApiResponse;

final class EditProductFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'slug' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'min:2', 'max:50'],
            'price' => ['required', 'numeric', 'min:0'],
            'quantity' => ['required', 'integer', 'min:0'],
            'compare_price' => ['nullable', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'min_quantity' => ['nullable', 'integer', 'min:0'],
            'max_quantity' => ['nullable', 'integer', 'min:1'],
            'track_quantity' => ['nullable', 'boolean'],
            'is_visible' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'is_digital' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string'],
            'short_description' => ['nullable', 'string'],
            'barcode' => ['nullable', 'string'],
            'digital_product_url' => ['nullable', 'string'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'height' => ['nullable', 'numeric', 'min:0'],
            'width' => ['nullable', 'numeric', 'min:0'],
            'length' => ['nullable', 'numeric', 'min:0'],
            'category_id' => ['nullable', 'integer'],
            'brand_id' => ['nullable', 'integer'],
            'published_at' => ['nullable', 'date'],
            'seo' => ['nullable', 'array'],
            'specifications' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
            'images' => ['nullable', 'array'],
            'variants' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del producto es obligatorio.',
            'slug.required' => 'El slug del producto es obligatorio.',
            'sku.required' => 'El SKU del producto es obligatorio.',
            'price.required' => 'El precio del producto es obligatorio.',
            'price.min' => 'El precio no puede ser negativo.',
            'quantity.required' => 'La cantidad en stock es obligatoria.',
            'quantity.min' => 'La cantidad en stock no puede ser negativa.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            ApiResponse::error(
                message: 'Error de validación al editar el producto',
                code: 422,
                errors: $validator->errors()->toArray()
            )
        );
    }
}
