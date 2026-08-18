<?php

declare(strict_types=1);

namespace Src\Order\Infrastructure\Http\Request;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Src\Order\Application\DTOs\FilterOrdersCriteria;

final class FilterOrdersFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'customer_id' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'in:pending,confirmed,processing,shipped,delivered,cancelled,refunded'],
            'payment_status' => ['nullable', 'string', 'in:pending,paid,failed,refunded'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'sort_by' => ['nullable', 'string', 'in:created_at,total,order_number,status,payment_status'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
        ];
    }

    public function toDto(): FilterOrdersCriteria
    {
        return FilterOrdersCriteria::fromArray($this->validated());
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'code' => 422,
            'message' => 'Error de validación en los filtros de búsqueda.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
