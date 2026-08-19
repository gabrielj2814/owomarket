<?php

declare(strict_types=1);

namespace Src\Billing\Infrastructure\Http\Request;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Src\Billing\Application\DTOs\UpdateBillingProfileData;
use Src\Shared\Helper\ApiResponse;

final class UpdateBillingProfileFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'legal_name' => ['required', 'string', 'min:2', 'max:255'],
            'tax_id' => ['required', 'string', 'min:3', 'max:30'],
            'billing_email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address_line_1' => ['required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'postal_code' => ['required', 'string', 'max:20'],
            'country' => ['required', 'string', 'max:100'],
            'invoice_prefix' => ['nullable', 'string', 'max:10'],
            'next_invoice_number' => ['nullable', 'integer', 'min:1'],
            'invoice_footer_notes' => ['nullable', 'string'],
            'logo_path' => ['nullable', 'string', 'max:500'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function toDto(): UpdateBillingProfileData
    {
        return new UpdateBillingProfileData(
            legal_name: (string) $this->input('legal_name'),
            tax_id: (string) $this->input('tax_id'),
            billing_email: (string) $this->input('billing_email'),
            phone: $this->input('phone'),
            address_line_1: (string) $this->input('address_line_1'),
            address_line_2: $this->input('address_line_2'),
            city: (string) $this->input('city'),
            state: (string) $this->input('state'),
            postal_code: (string) $this->input('postal_code'),
            country: (string) $this->input('country'),
            invoice_prefix: (string) $this->input('invoice_prefix', 'FAC-'),
            next_invoice_number: (int) $this->input('next_invoice_number', 1),
            invoice_footer_notes: $this->input('invoice_footer_notes'),
            logo_path: $this->input('logo_path'),
            metadata: $this->input('metadata')
        );
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ApiResponse::error(
                message: 'Error de validación en los datos fiscales del perfil',
                code: 422,
                errors: $validator->errors()->toArray()
            )
        );
    }
}
