<?php

declare(strict_types=1);

namespace Src\Review\Infrastructure\Http\Request;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Src\Review\Application\DTOs\FilterReviewsCriteria;

class FilterReviewsFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string'],
            'product_id' => ['nullable', 'string'],
            'customer_id' => ['nullable', 'string'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'is_approved' => ['nullable', 'boolean'],
            'is_verified' => ['nullable', 'boolean'],
            'has_response' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort_by' => ['nullable', 'string'],
            'sort_direction' => ['nullable', 'in:asc,desc'],
        ];
    }

    public function toDto(): FilterReviewsCriteria
    {
        return FilterReviewsCriteria::fromArray($this->validated());
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'code' => 422,
            'message' => 'Error de validación en filtros de reseñas.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
