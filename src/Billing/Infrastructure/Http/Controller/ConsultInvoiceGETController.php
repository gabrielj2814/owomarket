<?php

declare(strict_types=1);

namespace Src\Billing\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Src\Billing\Application\UseCases\ConsultInvoiceByIdUseCase;
use Src\Billing\Domain\Exceptions\InvoiceNotFoundException;
use Src\Shared\Helper\ApiResponse;

final class ConsultInvoiceGETController extends Controller
{
    public function __construct(
        private readonly ConsultInvoiceByIdUseCase $useCase
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        try {
            $invoice = $this->useCase->execute($id);

            return ApiResponse::success(
                data: $invoice->toArray(),
                message: 'Factura consultada exitosamente'
            );
        } catch (InvoiceNotFoundException $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                code: 404
            );
        }
    }
}
