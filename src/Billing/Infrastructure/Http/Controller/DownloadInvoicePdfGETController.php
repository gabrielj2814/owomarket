<?php

declare(strict_types=1);

namespace Src\Billing\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Src\Billing\Application\UseCases\GenerateInvoicePdfUseCase;
use Src\Billing\Domain\Exceptions\InvoiceNotFoundException;
use Src\Shared\Helper\ApiResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

final class DownloadInvoicePdfGETController extends Controller
{
    public function __construct(
        private readonly GenerateInvoicePdfUseCase $useCase
    ) {}

    public function __invoke(string $id): Response|SymfonyResponse
    {
        try {
            $result = $this->useCase->execute($id);

            return response($result['pdf_content'], 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "inline; filename=\"{$result['filename']}\"",
            ]);
        } catch (InvoiceNotFoundException $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                code: 404
            );
        }
    }
}
