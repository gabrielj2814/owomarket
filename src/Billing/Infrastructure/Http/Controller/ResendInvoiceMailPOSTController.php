<?php

declare(strict_types=1);

namespace Src\Billing\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Billing\Application\UseCases\ResendInvoiceMailUseCase;
use Src\Billing\Domain\Exceptions\InvoiceNotFoundException;
use Src\Shared\Helper\ApiResponse;

final class ResendInvoiceMailPOSTController extends Controller
{
    public function __construct(
        private readonly ResendInvoiceMailUseCase $useCase
    ) {}

    public function __invoke(string $id, Request $request): JsonResponse
    {
        try {
            $email = $request->input('email');
            $invoice = $this->useCase->execute($id, $email ? (string) $email : null);

            $targetEmail = $email ?: $invoice->customer()->email();

            return ApiResponse::success(
                data: $invoice->toArray(),
                message: "Factura reenviada exitosamente a {$targetEmail}"
            );
        } catch (InvoiceNotFoundException $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                code: 404
            );
        }
    }
}
