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
            // Hallazgo N12: era `required`, pero `TenantProductDetailPage.tsx` NUNCA lo
            // enviaba, asi que el formulario de resenas del storefront devolvia 422
            // garantizado — llevaba roto desde siempre. Y pedirselo al navegador era
            // ademas el mismo error que A3/A6: la identidad no la manda el cliente.
            // Ahora lo pone el servidor desde la sesion (`prepareForValidation`).
            'customer_id' => ['required', 'string', 'exists:customers,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'order_id' => ['nullable', 'string', 'exists:orders,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'comment' => ['nullable', 'string', 'max:2000'],
            // 'is_approved' e 'is_verified' YA NO se aceptan del cliente
            // (hallazgo B2). Antes, un POST con {"is_approved":true,
            // "is_verified":true} publicaba al instante una reseña de 5
            // estrellas marcada como "compra verificada", saltándose la
            // moderación. Ahora:
            //   - is_approved nace SIEMPRE en false; aprobar es potestad del
            //     comerciante vía ModerateReviewPOSTController.
            //   - is_verified lo decide el servidor con VerifiedPurchaseChecker,
            //     comprobando que el pedido sea de quien reseña y contenga
            //     el producto reseñado.
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

    /**
     * La identidad de quien resena sale de la sesion de cliente de la tienda, la misma
     * que crea `ConsumeSsoTokenPOSTController`. Si el navegador manda un `customer_id`,
     * se ignora.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'customer_id' => session('tenant_customer_id') ?? $this->input('customer_id'),
        ]);
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
