<?php

declare(strict_types=1);

namespace Src\Order\Infrastructure\Http\Request;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Src\Order\Application\DTOs\CreateOrderData;

final class CreateOrderFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'string', 'exists:customers,id'],
            'payment_method' => ['required', 'string', 'max:50'],
            'currency' => ['nullable', 'string', 'size:3'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'shipping_amount' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'order_number' => ['nullable', 'string', 'max:50', 'unique:orders,order_number'],
            'shipping_method' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'customer_note' => ['nullable', 'string', 'max:1000'],
            'metadata' => ['nullable', 'array'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'string', 'exists:products,id'],
            'items.*.product_name' => ['required', 'string', 'max:255'],
            'items.*.sku' => ['required', 'string', 'max:100'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.product_variant_id' => ['nullable', 'string'],
            'items.*.attributes' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'El cliente es obligatorio.',
            'customer_id.exists' => 'El cliente especificado no existe.',
            'payment_method.required' => 'El método de pago es obligatorio.',
            'items.required' => 'Debe incluir al menos un producto en la orden.',
            'items.min' => 'Debe incluir al menos un producto en la orden.',
            'items.*.product_id.required' => 'El ID del producto es obligatorio.',
            'items.*.product_id.exists' => 'Uno de los productos seleccionados no existe.',
            'items.*.product_name.required' => 'El nombre del producto es obligatorio.',
            'items.*.sku.required' => 'El SKU del producto es obligatorio.',
            'items.*.price.required' => 'El precio del producto es obligatorio.',
            'items.*.price.min' => 'El precio no puede ser negativo.',
            'items.*.quantity.required' => 'La cantidad es obligatoria.',
            'items.*.quantity.min' => 'La cantidad debe ser al menos 1.',
        ];
    }

    public function toDto(): CreateOrderData
    {
        return CreateOrderData::fromArray($this->validated());
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'code' => 422,
            'message' => 'Error de validación en los datos de la orden.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
