<?php

declare(strict_types=1);

namespace Src\Order\Infrastructure\Http\Request;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

final class UpdateOrderPaymentStatusFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_status' => ['required', 'string', 'in:pending,paid,failed,refunded'],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_status.required' => 'El estado del pago es obligatorio.',
            'payment_status.in' => 'El estado del pago no es válido.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'code' => 422,
            'message' => 'Error de validación al actualizar estado de pago.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
