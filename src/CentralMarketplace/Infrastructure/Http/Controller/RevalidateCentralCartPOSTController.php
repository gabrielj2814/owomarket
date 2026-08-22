<?php

declare(strict_types=1);

namespace Src\CentralMarketplace\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\CentralMarketplace\Application\Service\CentralCartRevalidator;
use Src\Shared\Helper\ApiResponse;

/**
 * Hallazgo N31: la Fase 3.2 dio revalidacion al carrito de cada tienda y dejo fuera el del
 * marketplace central, que seguia con precios y stock congelados en `localStorage`.
 */
final class RevalidateCentralCartPOSTController extends Controller
{
    public function __construct(
        private readonly CentralCartRevalidator $revalidator
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'items' => ['required', 'array', 'max:100'],
            'items.*.tenant_id' => ['required', 'string'],
            'items.*.product_id' => ['required', 'string'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $result = $this->revalidator->revalidate($request->input('items', []));

        return ApiResponse::success(
            data: $result,
            message: $result['has_changes']
                ? 'El carrito ha cambiado desde la ultima vez.'
                : 'El carrito esta al dia.'
        );
    }
}
