<?php

declare(strict_types=1);

namespace Src\Shipment\Infrastructure\Http\Request;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Src\Shipment\Application\DTOs\UpdateTrackingData;

final class UpdateShipmentTrackingFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tracking_number' => ['required', 'string', 'min:3', 'max:100'],
            'carrier' => ['nullable', 'string', 'min:2', 'max:100'],
            'service' => ['nullable', 'string', 'min:2', 'max:100'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'shipped_at' => ['nullable', 'date'],
            'estimated_delivery' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'tracking_number.required' => 'El número de seguimiento es obligatorio.',
            'tracking_number.min' => 'El número de seguimiento debe tener al menos 3 caracteres.',
            'cost.numeric' => 'El costo debe ser un valor numérico.',
            'cost.min' => 'El costo no puede ser negativo.',
        ];
    }

    public function toDto(): UpdateTrackingData
    {
        return UpdateTrackingData::fromArray($this->validated());
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'code' => 422,
            'message' => 'Error de validación al actualizar el seguimiento del envío.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
