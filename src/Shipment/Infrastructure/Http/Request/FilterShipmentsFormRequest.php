<?php

declare(strict_types=1);

namespace Src\Shipment\Infrastructure\Http\Request;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Src\Shipment\Application\DTOs\FilterShipmentsCriteria;

final class FilterShipmentsFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'in:pending,in_transit,delivered'],
            'carrier' => ['nullable', 'string'],
            'order_id' => ['nullable', 'string'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort_by' => ['nullable', 'string', 'in:created_at,shipped_at,delivered_at,carrier,cost'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
        ];
    }

    public function toDto(): FilterShipmentsCriteria
    {
        return FilterShipmentsCriteria::fromArray($this->validated());
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'code' => 422,
            'message' => 'Parámetros de filtrado no válidos.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
