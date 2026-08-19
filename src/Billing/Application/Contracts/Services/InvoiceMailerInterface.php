<?php

declare(strict_types=1);

namespace Src\Billing\Application\Contracts\Services;

use Src\Billing\Domain\Entities\Invoice;

interface InvoiceMailerInterface
{
    /**
     * Envía la factura con el archivo PDF adjunto al correo del cliente.
     */
    public function sendInvoiceEmail(Invoice $invoice, ?string $recipientEmail = null): bool;
}
