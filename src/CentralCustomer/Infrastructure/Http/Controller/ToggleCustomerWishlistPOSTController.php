<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\CentralCustomer\Application\UseCases\ToggleCustomerWishlistProductUseCase;

final class ToggleCustomerWishlistPOSTController
{
    public function __construct(
        private readonly ToggleCustomerWishlistProductUseCase $toggleWishlistUseCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'string'],
            'product_id' => ['required', 'string'],
            'tenant_id' => ['required', 'string'],
            'product_name' => ['required', 'string'],
            'product_slug' => ['nullable', 'string'],
            'product_price' => ['required', 'numeric'],
            'product_image' => ['nullable', 'string'],
        ]);

        try {
            $result = $this->toggleWishlistUseCase->execute($validated['customer_id'], $validated);

            return response()->json([
                'code' => 200,
                'status' => 'success',
                'message' => $result['message'],
                'data' => [
                    'in_wishlist' => $result['in_wishlist'],
                ],
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
