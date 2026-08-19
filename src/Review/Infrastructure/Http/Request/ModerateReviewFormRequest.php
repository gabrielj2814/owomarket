<?php

declare(strict_types=1);

namespace Src\Review\Infrastructure\Http\Request;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Src\Review\Application\DTOs\ModerateReviewData;

class ModerateReviewFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_approved' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'is_approved.required' => 'El estado de aprobación es obligatorio.',
            'is_approved.boolean' => 'El estado de aprobación debe ser verdadero o falso.',
        ];
    }

    public function toDto(string $id): ModerateReviewData
    {
        return new ModerateReviewData(
            id: $id,
            isApproved: (bool) $this->input('is_approved')
        );
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'code' => 422,
            'message' => 'Error de validación al moderar la reseña.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
