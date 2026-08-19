<?php

declare(strict_types=1);

namespace Src\Billing\Infrastructure\Services;

use Illuminate\Support\Facades\Mail;
use Src\Billing\Application\Contracts\Services\InvoiceMailerInterface;
use Src\Billing\Application\Contracts\Services\InvoicePdfGeneratorInterface;
use Src\Billing\Domain\Entities\Invoice;
use Src\Billing\Infrastructure\Mail\InvoiceMail;

final class LaravelInvoiceMailerService implements InvoiceMailerInterface
{
    public function __construct(
        private readonly InvoicePdfGeneratorInterface $pdfGenerator
    ) {}

    public function sendInvoiceEmail(Invoice $invoice, ?string $recipientEmail = null): bool
    {
        $targetEmail = ! empty($recipientEmail) ? $recipientEmail : $invoice->customer()->email();

        if (empty($targetEmail)) {
            return false;
        }

        $pdfBinary = $this->pdfGenerator->generate($invoice);

        Mail::to($targetEmail)->send(new InvoiceMail($invoice, $pdfBinary));

        return true;
    }
}
