<?php

declare(strict_types=1);

namespace Src\Billing\Application\UseCases;

use Src\Billing\Application\Contracts\Repositories\InvoiceRepositoryInterface;
use Src\Billing\Application\Contracts\Services\InvoicePdfGeneratorInterface;
use Src\Billing\Domain\Entities\Invoice;
use Src\Billing\Domain\Exceptions\InvoiceNotFoundException;
use Src\Billing\Domain\ValueObjects\InvoiceId;

final class GenerateInvoicePdfUseCase
{
    public function __construct(
        private readonly InvoiceRepositoryInterface $invoiceRepository,
        private readonly InvoicePdfGeneratorInterface $pdfGenerator
    ) {}

    /**
     * @return array{invoice: Invoice, pdf_content: string, filename: string}
     */
    public function execute(string $id): array
    {
        $invoice = $this->invoiceRepository->findById(InvoiceId::fromString($id));

        if (! $invoice) {
            throw InvoiceNotFoundException::withId($id);
        }

        $pdfContent = $this->pdfGenerator->generate($invoice);
        $filename = "{$invoice->invoiceNumber()->value()}.pdf";

        return [
            'invoice' => $invoice,
            'pdf_content' => $pdfContent,
            'filename' => $filename,
        ];
    }
}
