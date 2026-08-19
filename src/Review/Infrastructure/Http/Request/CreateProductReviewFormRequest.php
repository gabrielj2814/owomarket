<?php

declare(strict_types=1);

namespace Src\Review\Infrastructure\Http\Request;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Src\Review\Application\DTOs\CreateReviewData;

class CreateProductReviewFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'string', 'exists:products,id'],
            'customer_id' => ['required', 'string', 'exists:customers,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'order_id' => ['nullable', 'string', 'exists:orders,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'comment' => ['nullable', 'string', 'max:2000'],
            'is_approved' => ['nullable', 'boolean'],
            'is_verified' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'El producto a calificar es obligatorio.',
            'product_id.exists' => 'El producto seleccionado no existe en el catálogo.',
            'customer_id.required' => 'El cliente es obligatorio.',
            'customer_id.exists' => 'El cliente especificado no existe.',
            'rating.required' => 'La calificación es obligatoria.',
            'rating.integer' => 'La calificación debe ser un número entero.',
            'rating.min' => 'La calificación mínima es de 1 estrella.',
            'rating.max' => 'La calificación máxima es de 5 estrellas.',
            'order_id.exists' => 'La orden especificada no existe.',
        ];
    }

    public function toDto(): CreateReviewData
    {
        return CreateReviewData::fromArray($this->validated());
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'code' => 422,
            'message' => 'Error de validación al crear la reseña.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
