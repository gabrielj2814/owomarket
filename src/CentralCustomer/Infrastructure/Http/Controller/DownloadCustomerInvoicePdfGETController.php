<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Src\CentralCustomer\Application\UseCases\DownloadCustomerInvoicePdfUseCase;
use Src\CentralCustomer\Infrastructure\Http\Support\ResolvesAuthenticatedCustomer;

final class DownloadCustomerInvoicePdfGETController
{
    use ResolvesAuthenticatedCustomer;

    public function __construct(
        private readonly DownloadCustomerInvoicePdfUseCase $downloadPdfUseCase
    ) {}

    public function __invoke(Request $request, string $id): Response|JsonResponse
    {
        $customerIdStr = $this->currentCustomerId();

        try {
            $pdf = $this->downloadPdfUseCase->execute($customerIdStr, $id);

            return response($pdf['content'], 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "attachment; filename=\"{$pdf['filename']}\"",
            ]);
        } catch (Exception $e) {
            $code = is_numeric($e->getCode()) ? (int) $e->getCode() : 404;
            $status = $code >= 400 && $code < 600 ? $code : 404;

            return response()->json([
                'code' => $status,
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $status);
        }
    }
}
