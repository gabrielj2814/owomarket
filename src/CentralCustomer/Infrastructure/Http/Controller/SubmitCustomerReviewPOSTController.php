<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\CentralCustomer\Application\UseCases\SubmitCustomerProductReviewUseCase;
use Src\CentralCustomer\Infrastructure\Http\Support\ResolvesAuthenticatedCustomer;

final class SubmitCustomerReviewPOSTController
{
    use ResolvesAuthenticatedCustomer;

    public function __construct(
        private readonly SubmitCustomerProductReviewUseCase $submitReviewUseCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => ['required', 'string'],
            'product_id' => ['required', 'string'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:255'],
            'comment' => ['required', 'string', 'max:1000'],
        ]);

        // Antes 'customer_id' salía del body: cualquiera podía publicar una
        // reseña "verificada" a nombre de otro comprador (hallazgo A3).
        $customerId = $this->currentCustomerId();

        try {
            $result = $this->submitReviewUseCase->execute($customerId, $validated);

            return response()->json([
                'code' => 200,
                'status' => 'success',
                'message' => $result['message'],
            ]);
        } catch (Exception $e) {
            $code = is_numeric($e->getCode()) ? (int) $e->getCode() : 400;
            $status = $code >= 400 && $code < 600 ? $code : 400;

            return response()->json([
                'code' => $status,
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $status);
        }
    }
}
