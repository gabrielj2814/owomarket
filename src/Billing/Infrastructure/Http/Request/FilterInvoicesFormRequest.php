<?php

declare(strict_types=1);

namespace Src\Billing\Infrastructure\Http\Request;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Src\Billing\Application\DTOs\FilterInvoicesCriteria;
use Src\Shared\Helper\ApiResponse;

final class FilterInvoicesFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'in:draft,issued,paid,cancelled,refunded'],
            'payment_status' => ['nullable', 'string', 'in:paid,pending,failed'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'min_total' => ['nullable', 'numeric', 'min:0'],
            'max_total' => ['nullable', 'numeric', 'min:0'],
            'sort_by' => ['nullable', 'string', 'in:invoice_number,issue_date,total,created_at,status'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function toCriteria(): FilterInvoicesCriteria
    {
        return new FilterInvoicesCriteria(
            search: $this->input('search'),
            status: $this->input('status'),
            payment_status: $this->input('payment_status'),
            payment_method: $this->input('payment_method'),
            date_from: $this->input('date_from'),
            date_to: $this->input('date_to'),
            min_total: $this->input('min_total') !== null ? (float) $this->input('min_total') : null,
            max_total: $this->input('max_total') !== null ? (float) $this->input('max_total') : null,
            sort_by: (string) $this->input('sort_by', 'created_at'),
            sort_direction: (string) $this->input('sort_direction', 'desc'),
            page: (int) $this->input('page', 1),
            per_page: (int) $this->input('per_page', 15)
        );
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ApiResponse::error(
                message: 'Error de validación en filtros de facturación',
                code: 422,
                errors: $validator->errors()->toArray()
            )
        );
    }
}
