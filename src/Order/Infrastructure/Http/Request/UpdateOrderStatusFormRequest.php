<?php

declare(strict_types=1);

namespace Src\Order\Infrastructure\Http\Request;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

final class UpdateOrderStatusFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:confirmed,processing,shipped,delivered,cancelled,refunded'],
            'shipping_method' => ['nullable', 'string', 'max:100'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'El nuevo estado de la orden es obligatorio.',
            'status.in' => 'El estado proporcionado no es válido.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'code' => 422,
            'message' => 'Error de validación al actualizar estado de la orden.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
