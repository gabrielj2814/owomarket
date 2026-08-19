<?php

declare(strict_types=1);

namespace Src\Payment\Infrastructure\Http\Request;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Src\Payment\Application\DTOs\ProcessPaymentData;
use Src\Shared\Helper\ApiResponse;

final class ProcessPaymentFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['required', 'string', 'size:3'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_name' => ['required', 'string', 'min:2', 'max:255'],
            'payment_method' => ['required', 'string', 'max:50'],
            'order_id' => ['nullable', 'string'],
            'description' => ['nullable', 'string', 'max:500'],
            'return_url' => ['nullable', 'url'],
            'cancel_url' => ['nullable', 'url'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function toDto(): ProcessPaymentData
    {
        return new ProcessPaymentData(
            amount: (float) $this->input('amount'),
            currency: strtoupper(trim((string) $this->input('currency'))),
            customer_email: (string) $this->input('customer_email'),
            customer_name: (string) $this->input('customer_name'),
            payment_method: (string) $this->input('payment_method'),
            order_id: $this->input('order_id'),
            description: $this->input('description'),
            return_url: $this->input('return_url'),
            cancel_url: $this->input('cancel_url'),
            metadata: $this->input('metadata')
        );
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ApiResponse::error(
                message: 'Error de validación al procesar el pago',
                code: 422,
                errors: $validator->errors()->toArray()
            )
        );
    }
}
