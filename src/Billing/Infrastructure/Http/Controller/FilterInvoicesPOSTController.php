<?php

declare(strict_types=1);

namespace Src\Billing\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Src\Billing\Application\UseCases\FilterInvoicesUseCase;
use Src\Billing\Infrastructure\Http\Request\FilterInvoicesFormRequest;
use Src\Shared\Helper\ApiResponse;

final class FilterInvoicesPOSTController extends Controller
{
    public function __construct(
        private readonly FilterInvoicesUseCase $useCase
    ) {}

    public function __invoke(FilterInvoicesFormRequest $request): JsonResponse
    {
        $criteria = $request->toCriteria();
        $result = $this->useCase->execute($criteria);

        return ApiResponse::success(
            data: $result->toArray(),
            message: 'Facturas listadas exitosamente'
        );
    }
}
