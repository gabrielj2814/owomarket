<?php

declare(strict_types=1);

namespace Src\Review\Infrastructure\Http\Request;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Src\Review\Application\DTOs\RespondReviewData;

class RespondReviewFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'response' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'response.max' => 'La respuesta no puede exceder los 2000 caracteres.',
        ];
    }

    public function toDto(string $id): RespondReviewData
    {
        return new RespondReviewData(
            id: $id,
            response: (string) ($this->input('response') ?? '')
        );
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'code' => 422,
            'message' => 'Error de validación al responder la reseña.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
