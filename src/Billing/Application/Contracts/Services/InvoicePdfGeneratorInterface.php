<?php

declare(strict_types=1);

namespace Src\Billing\Application\Contracts\Services;

use Src\Billing\Domain\Entities\Invoice;

interface InvoicePdfGeneratorInterface
{
    /**
     * Genera el contenido binario crudo del PDF para la factura especificada.
     */
    public function generate(Invoice $invoice): string;

    /**
     * Guarda el PDF físicamente en el storage del tenant y devuelve la ruta relativa.
     */
    public function saveToStorage(Invoice $invoice): string;
}
