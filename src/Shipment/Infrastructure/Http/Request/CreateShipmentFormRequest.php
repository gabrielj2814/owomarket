<?php

declare(strict_types=1);

namespace Src\Shipment\Infrastructure\Http\Request;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Src\Shipment\Application\DTOs\CreateShipmentData;

final class CreateShipmentFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'string', 'exists:orders,id'],
            'carrier' => ['required', 'string', 'min:2', 'max:100'],
            'service' => ['required', 'string', 'min:2', 'max:100'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'tracking_number' => ['nullable', 'string', 'min:3', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'estimated_delivery' => ['nullable', 'date'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.required' => 'La orden asociada es obligatoria.',
            'order_id.exists' => 'La orden especificada no existe.',
            'carrier.required' => 'La empresa de transporte es obligatoria.',
            'carrier.min' => 'El nombre del transportista debe tener al menos 2 caracteres.',
            'service.required' => 'El tipo de servicio de despacho es obligatorio.',
            'cost.numeric' => 'El costo de despacho debe ser un valor numérico.',
            'cost.min' => 'El costo de despacho no puede ser negativo.',
            'tracking_number.min' => 'El número de seguimiento debe tener al menos 3 caracteres.',
        ];
    }

    public function toDto(): CreateShipmentData
    {
        return CreateShipmentData::fromArray($this->validated());
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'code' => 422,
            'message' => 'Error de validación al registrar el envío.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
