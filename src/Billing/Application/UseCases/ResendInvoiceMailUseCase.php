<?php

declare(strict_types=1);

namespace Src\Billing\Application\UseCases;

use Src\Billing\Application\Contracts\Repositories\InvoiceRepositoryInterface;
use Src\Billing\Application\Contracts\Services\InvoiceMailerInterface;
use Src\Billing\Domain\Entities\Invoice;
use Src\Billing\Domain\Exceptions\InvoiceNotFoundException;
use Src\Billing\Domain\ValueObjects\InvoiceId;

final class ResendInvoiceMailUseCase
{
    public function __construct(
        private readonly InvoiceRepositoryInterface $invoiceRepository,
        private readonly InvoiceMailerInterface $mailer
    ) {}

    public function execute(string $id, ?string $recipientEmail = null): Invoice
    {
        $invoice = $this->invoiceRepository->findById(InvoiceId::fromString($id));

        if (! $invoice) {
            throw InvoiceNotFoundException::withId($id);
        }

        $this->mailer->sendInvoiceEmail($invoice, $recipientEmail);

        return $invoice;
    }
}
