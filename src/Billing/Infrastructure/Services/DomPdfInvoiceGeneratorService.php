<?php

declare(strict_types=1);

namespace Src\Billing\Infrastructure\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Src\Billing\Application\Contracts\Services\InvoicePdfGeneratorInterface;
use Src\Billing\Domain\Entities\Invoice;

final class DomPdfInvoiceGeneratorService implements InvoicePdfGeneratorInterface
{
    public function generate(Invoice $invoice): string
    {
        $pdf = Pdf::loadView('invoices.pdf', [
            'invoice' => $invoice->toArray(),
        ]);

        $pdf->setPaper('a4', 'portrait');

        return (string) $pdf->output();
    }

    public function saveToStorage(Invoice $invoice): string
    {
        $tenantId = tenancy()->initialized ? tenant('id') : 'global';
        $filename = "{$invoice->invoiceNumber()->value()}.pdf";
        $relativePath = "tenants/{$tenantId}/invoices/{$filename}";

        $binary = $this->generate($invoice);

        Storage::disk('public')->put($relativePath, $binary);

        $invoice->setPdfPath($relativePath);

        return $relativePath;
    }
}
