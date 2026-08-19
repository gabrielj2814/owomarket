<?php

declare(strict_types=1);

namespace Src\Coupon\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Coupon\Application\DTOs\CouponFilterCriteria;
use Src\Coupon\Application\UseCase\FilterCouponsUseCase;
use Src\Shared\Helper\ApiResponse;

final class FilterCouponsPOSTController
{
    public function __construct(
        private readonly FilterCouponsUseCase $useCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $criteria = new CouponFilterCriteria(
                search: $request->filled('search') ? (string) $request->input('search') : null,
                type: $request->filled('type') ? (string) $request->input('type') : null,
                isActive: $request->has('is_active') && $request->input('is_active') !== '' && $request->input('is_active') !== null
                    ? filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
                    : null,
                validDate: $request->filled('valid_date') ? (string) $request->input('valid_date') : null,
                page: (int) $request->input('page', 1),
                perPage: (int) $request->input('prePage', $request->input('perPage', 10)),
                sortBy: (string) $request->input('sortBy', 'created_at'),
                sortDirection: (string) $request->input('sortDirection', 'desc')
            );

            $result = $this->useCase->execute($criteria);

            $data = array_map(fn ($coupon) => $coupon->toArray(), $result->items);

            $pagination = [
                'total' => $result->total,
                'current_page' => $result->currentPage,
                'per_page' => $result->perPage,
                'last_page' => $result->lastPage,
            ];

            return ApiResponse::Pagination(
                data: $data,
                message: 'Cupones listados exitosamente',
                code: 200,
                pagination: $pagination
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                code: (int) ($e->getCode() ?: 400)
            );
        }
    }
}
