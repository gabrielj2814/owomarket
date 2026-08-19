<?php

declare(strict_types=1);

namespace Src\Review\Infrastructure\Http\Request;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Src\Review\Application\DTOs\UpdateReviewData;

class UpdateProductReviewFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:255'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'rating.required' => 'La calificación es obligatoria.',
            'rating.integer' => 'La calificación debe ser un número entero.',
            'rating.min' => 'La calificación mínima es de 1 estrella.',
            'rating.max' => 'La calificación máxima es de 5 estrellas.',
        ];
    }

    public function toDto(string $id): UpdateReviewData
    {
        return new UpdateReviewData(
            id: $id,
            rating: (int) $this->input('rating'),
            title: $this->input('title') ? (string) $this->input('title') : null,
            comment: $this->input('comment') ? (string) $this->input('comment') : null
        );
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'code' => 422,
            'message' => 'Error de validación al actualizar la reseña.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
