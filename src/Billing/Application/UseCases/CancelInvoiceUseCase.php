<?php

declare(strict_types=1);

namespace Src\Billing\Application\UseCases;

use Src\Billing\Application\Contracts\Repositories\InvoiceRepositoryInterface;
use Src\Billing\Domain\Entities\Invoice;
use Src\Billing\Domain\Exceptions\InvoiceNotFoundException;
use Src\Billing\Domain\ValueObjects\InvoiceId;

final class CancelInvoiceUseCase
{
    public function __construct(
        private readonly InvoiceRepositoryInterface $repository
    ) {}

    public function execute(string $id, string $reason = ''): Invoice
    {
        $invoice = $this->repository->findById(InvoiceId::fromString($id));

        if (! $invoice) {
            throw InvoiceNotFoundException::withId($id);
        }

        $invoice->cancel($reason);

        return $this->repository->update($invoice);
    }
}
