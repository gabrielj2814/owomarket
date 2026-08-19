<?php

declare(strict_types=1);

namespace Src\Billing\Infrastructure\Http\Request;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Src\Billing\Application\DTOs\CreateDirectInvoiceData;
use Src\Billing\Application\DTOs\InvoiceItemData;
use Src\Shared\Helper\ApiResponse;

final class CreateDirectInvoiceFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'min:2', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_tax_id' => ['nullable', 'string', 'max:30'],
            'customer_address_line_1' => ['required', 'string', 'max:255'],
            'customer_address_line_2' => ['nullable', 'string', 'max:255'],
            'customer_city' => ['required', 'string', 'max:100'],
            'customer_state' => ['required', 'string', 'max:100'],
            'customer_postal_code' => ['required', 'string', 'max:20'],
            'customer_country' => ['required', 'string', 'max:100'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'min:1', 'max:500'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.product_id' => ['nullable', 'string'],
            'items.*.product_variant_id' => ['nullable', 'string'],
            'items.*.sku' => ['nullable', 'string', 'max:100'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'payment_status' => ['nullable', 'string', 'in:paid,pending'],
            'status' => ['nullable', 'string', 'in:draft,issued,paid'],
            'issue_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'currency' => ['nullable', 'string', 'size:3'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function toDto(): CreateDirectInvoiceData
    {
        $itemsData = array_map(function (array $item) {
            return new InvoiceItemData(
                description: (string) $item['description'],
                quantity: (int) $item['quantity'],
                unit_price: (float) $item['unit_price'],
                tax_rate: isset($item['tax_rate']) ? (float) $item['tax_rate'] : 0.0,
                discount_amount: isset($item['discount_amount']) ? (float) $item['discount_amount'] : 0.0,
                product_id: $item['product_id'] ?? null,
                product_variant_id: $item['product_variant_id'] ?? null,
                sku: $item['sku'] ?? null
            );
        }, (array) $this->input('items', []));

        return new CreateDirectInvoiceData(
            customer_name: (string) $this->input('customer_name'),
            customer_email: (string) $this->input('customer_email'),
            customer_tax_id: $this->input('customer_tax_id'),
            customer_address_line_1: (string) $this->input('customer_address_line_1'),
            customer_address_line_2: $this->input('customer_address_line_2'),
            customer_city: (string) $this->input('customer_city'),
            customer_state: (string) $this->input('customer_state'),
            customer_postal_code: (string) $this->input('customer_postal_code'),
            customer_country: (string) $this->input('customer_country'),
            items: $itemsData,
            payment_method: (string) $this->input('payment_method', 'manual'),
            payment_status: (string) $this->input('payment_status', 'paid'),
            status: (string) $this->input('status', 'issued'),
            issue_date: $this->input('issue_date'),
            due_date: $this->input('due_date'),
            currency: (string) $this->input('currency', 'USD'),
            notes: $this->input('notes'),
            order_id: $this->input('order_id'),
            customer_id: $this->input('customer_id'),
            metadata: $this->input('metadata')
        );
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ApiResponse::error(
                message: 'Error de validación al emitir factura',
                code: 422,
                errors: $validator->errors()->toArray()
            )
        );
    }
}
