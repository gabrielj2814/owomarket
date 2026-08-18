<?php

declare(strict_types=1);

namespace Src\Billing\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Src\Billing\Application\UseCases\CreateDirectInvoiceUseCase;
use Src\Billing\Infrastructure\Http\Request\CreateDirectInvoiceFormRequest;
use Src\Shared\Helper\ApiResponse;

final class CreateDirectInvoicePOSTController extends Controller
{
    public function __construct(
        private readonly CreateDirectInvoiceUseCase $useCase
    ) {}

    public function __invoke(CreateDirectInvoiceFormRequest $request): JsonResponse
    {
        $dto = $request->toDto();
        $invoice = $this->useCase->execute($dto);

        return ApiResponse::created(
            data: $invoice->toArray(),
            message: "Factura {$invoice->invoiceNumber()->value()} emitida exitosamente"
        );
    }
}
