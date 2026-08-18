<?php

declare(strict_types=1);

namespace Src\Billing\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Src\Billing\Application\UseCases\CancelInvoiceUseCase;
use Src\Billing\Domain\Exceptions\InvalidInvoiceStateException;
use Src\Billing\Domain\Exceptions\InvoiceNotFoundException;
use Src\Billing\Infrastructure\Http\Request\CancelInvoiceFormRequest;
use Src\Shared\Helper\ApiResponse;

final class CancelInvoicePOSTController extends Controller
{
    public function __construct(
        private readonly CancelInvoiceUseCase $useCase
    ) {}

    public function __invoke(string $id, CancelInvoiceFormRequest $request): JsonResponse
    {
        try {
            $reason = (string) $request->input('reason', '');
            $invoice = $this->useCase->execute($id, $reason);

            return ApiResponse::success(
                data: $invoice->toArray(),
                message: "Factura {$invoice->invoiceNumber()->value()} anulada exitosamente"
            );
        } catch (InvoiceNotFoundException $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                code: 404
            );
        } catch (InvalidInvoiceStateException $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                code: 422
            );
        }
    }
}
